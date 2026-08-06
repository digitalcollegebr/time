<?php

namespace Leantime\Domain\Oidc\Services;

use Illuminate\Support\Facades\Log;
use Leantime\Core\Configuration\Environment;

/**
 * Decide quem pode entrar via OIDC.
 *
 * O OIDC nativo do Leantime não tem nenhuma restrição de identidade: qualquer
 * conta que o provedor autentique entra, e com auto-provisionamento ligado ganha
 * usuário automaticamente. Esta classe é o portão que faltava.
 *
 * Depende apenas da configuração — sem HTTP, sem banco, sem sessão — justamente
 * para ser testável de forma direta.
 */
class OidcAccessPolicy
{
    public function __construct(private Environment $config) {}

    /**
     * Valida a identidade devolvida pelo provedor. Falha fechado.
     *
     * @param  array  $claims  Claims do ID token (ou do userinfo).
     * @param  string  $email  E-mail já extraído das claims.
     *
     * @throws OidcAccessDeniedException Quando a identidade não pode entrar.
     */
    public function assertAllowed(array $claims, string $email): void
    {
        $allowedDomains = $this->allowedDomains();

        // Allowlist vazia com criação automática ligada significaria provisionar
        // conta para QUALQUER identidade do provedor. Em vez de confiar que
        // ninguém vai errar essa combinação, ela simplesmente não autentica.
        if ($allowedDomains === [] && $this->config->get('oidcCreateUser', false)) {
            $this->deny(
                'oidc.error.domainNotAllowed',
                'auto-provisionamento ligado sem LEAN_OIDC_ALLOWED_EMAIL_DOMAINS: negando todos os logins'
            );
        }

        // Sem allowlist e sem criação automática, preserva o comportamento do
        // upstream (só entra quem já tem conta).
        if ($allowedDomains === []) {
            return;
        }

        $emailDomain = $this->domainOf($email);

        if ($emailDomain === null) {
            $this->deny('oidc.error.domainNotAllowed', 'e-mail em formato inesperado: '.$email);
        }

        // Comparação EXATA, nunca sufixo: str_ends_with('x@fakedigitalcollege.com.br',
        // 'digitalcollege.com.br') seria true e deixaria entrar um domínio de terceiro.
        if (! in_array($emailDomain, $allowedDomains, true)) {
            $this->deny('oidc.error.domainNotAllowed', 'domínio fora da allowlist: '.$emailDomain);
        }

        if (! $this->claimIsTrue($claims, 'email_verified')) {
            $this->deny('oidc.error.domainNotAllowed', 'email_verified ausente ou falso para '.$email);
        }

        if ($this->config->get('oidcRequireHostedDomain', false)) {
            $this->assertHostedDomain($claims, $allowedDomains, $email);
        }
    }

    /**
     * Exige que a claim `hd` (hosted domain) aponte para um domínio permitido.
     *
     * Não é redundante com a checagem de e-mail: existe conta Google DE CONSUMIDOR
     * registrada com endereço de um domínio corporativo, criada fora do Workspace.
     * Ela traz o e-mail do domínio, mas não traz `hd`. Exigir `hd` é o que prende
     * o login ao tenant gerenciado, e não apenas ao texto do endereço.
     */
    private function assertHostedDomain(array $claims, array $allowedDomains, string $email): void
    {
        $hostedDomain = $claims['hd'] ?? null;

        if (! is_string($hostedDomain) || trim($hostedDomain) === '') {
            $this->deny('oidc.error.domainNotAllowed', 'claim hd ausente para '.$email);
        }

        $hostedDomain = mb_strtolower(trim($hostedDomain));

        if (! in_array($hostedDomain, $allowedDomains, true)) {
            $this->deny('oidc.error.domainNotAllowed', 'claim hd fora da allowlist: '.$hostedDomain);
        }
    }

    /**
     * Domínios permitidos, normalizados em minúsculas.
     *
     * @return string[]
     */
    private function allowedDomains(): array
    {
        $raw = (string) $this->config->get('oidcAllowedEmailDomains', '');

        $domains = array_map(
            static fn (string $domain): string => mb_strtolower(trim($domain)),
            explode(',', $raw)
        );

        return array_values(array_filter($domains, static fn (string $domain): bool => $domain !== ''));
    }

    /**
     * Parte do e-mail após o @, em minúsculas. Null quando não há exatamente um @.
     */
    private function domainOf(string $email): ?string
    {
        $email = trim($email);

        if (substr_count($email, '@') !== 1) {
            return null;
        }

        $domain = mb_strtolower(substr($email, strpos($email, '@') + 1));

        return $domain === '' ? null : $domain;
    }

    /**
     * Lê a claim direto do array, sem passar por readMultilayerKey().
     *
     * Aquele helper declara retorno `string` e coagiria um booleano `false` para
     * string vazia, o que aqui trocaria "não verificado" por "ausente".
     */
    private function claimIsTrue(array $claims, string $key): bool
    {
        if (! array_key_exists($key, $claims)) {
            return false;
        }

        return filter_var($claims[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }

    /**
     * @throws OidcAccessDeniedException
     */
    private function deny(string $translationKey, string $logReason): void
    {
        Log::warning('OIDC: acesso negado — '.$logReason);

        throw new OidcAccessDeniedException($translationKey, $logReason);
    }
}
