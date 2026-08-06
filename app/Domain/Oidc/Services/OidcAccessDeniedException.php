<?php

namespace Leantime\Domain\Oidc\Services;

/**
 * Login OIDC recusado pela política de acesso.
 *
 * Carrega DOIS textos distintos de propósito: a chave de tradução, genérica, que
 * é o que o usuário vê; e a mensagem da exceção, específica, que vai para o log.
 * Contar ao usuário qual regra falhou entregaria a um atacante um oráculo sobre
 * a configuração da allowlist.
 *
 * Estende RuntimeException para que os `catch` já existentes no fluxo OIDC
 * continuem funcionando sem alteração.
 */
class OidcAccessDeniedException extends \RuntimeException
{
    /**
     * @param  string  $translationKey  Chave de tradução exibida ao usuário (genérica).
     * @param  string  $logReason  Motivo específico da recusa, apenas para log.
     */
    public function __construct(
        private readonly string $translationKey,
        string $logReason
    ) {
        parent::__construct($logReason);
    }

    /**
     * Chave de tradução da mensagem genérica destinada ao usuário.
     */
    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }
}
