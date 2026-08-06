<?php

namespace Leantime\Domain\Oidc\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Leantime\Domain\Oidc\Services\Oidc as OidcService;
use Leantime\Domain\Oidc\Services\OidcUserMessageException;
use Symfony\Component\HttpFoundation\Response;

class Callback extends Controller
{
    private OidcService $oidc;

    public function init(OidcService $oidc): void
    {
        $this->oidc = $oidc;
    }

    /**
     * @throws GuzzleException|HttpResponseException
     */
    public function get($params): Response
    {
        // O provedor devolve ?error=access_denied quando o usuário clica em
        // "Cancelar" na tela de consentimento — ação corriqueira, não caso raro.
        // Sem este tratamento, `code` vem ausente e o TypeError resultante estoura
        // um 500 (TypeError estende Error, então nem era pego pelo catch abaixo).
        if (isset($_GET['error'])) {
            Log::warning('OIDC: provedor retornou erro no callback: '.$_GET['error']);

            return $this->reject('oidc.error.providerError');
        }

        if (! isset($_GET['code'], $_GET['state'])) {
            return $this->reject('oidc.error.invalidState');
        }

        try {
            return $this->oidc->callback((string) $_GET['code'], (string) $_GET['state']);
        } catch (OidcUserMessageException $e) {
            // Mensagem escrita para o usuário e já traduzida.
            $this->tpl->setNotification($e->getMessage(), 'danger', 'oidc_error');

            return Frontcontroller::redirect(BASE_URL.'/auth/login');
        } catch (\Throwable $e) {
            // Qualquer outra falha pode carregar detalhe interno (SQL, stack, URL
            // com segredo). Vai inteira para o log, e o usuário recebe o genérico.
            Log::error($e);

            return $this->reject('oidc.error.providerError');
        }
    }

    /**
     * Recusa o login com mensagem traduzida e volta para a tela de login.
     */
    private function reject(string $translationKey): Response
    {
        $this->tpl->setNotification($this->language->__($translationKey), 'danger', 'oidc_error');

        return Frontcontroller::redirect(BASE_URL.'/auth/login');
    }
}
