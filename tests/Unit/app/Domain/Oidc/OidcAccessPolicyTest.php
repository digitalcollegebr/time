<?php

namespace Tests\Unit\App\Domain\Oidc;

use Leantime\Core\Configuration\Environment;
use Leantime\Domain\Oidc\Services\OidcAccessDeniedException;
use Leantime\Domain\Oidc\Services\OidcAccessPolicy;

/**
 * Cobre quem pode entrar via OIDC.
 *
 * O caso que mais importa aqui é o do domínio parecido: comparar o domínio por
 * sufixo deixaria "alguem@fakedigitalcollege.com.br" entrar num sistema restrito
 * a "digitalcollege.com.br". É um erro fácil de reintroduzir numa refatoração,
 * por isso está fixado em teste.
 */
class OidcAccessPolicyTest extends \Unit\TestCase
{
    private const ALLOWED = 'digitalcollege.com.br';

    /**
     * Configuração de produção: allowlist ativa, hd exigido, criação automática ligada.
     */
    private function strictConfig(): array
    {
        return [
            'oidcAllowedEmailDomains' => self::ALLOWED,
            'oidcRequireHostedDomain' => true,
            'oidcCreateUser' => true,
        ];
    }

    private function policy(array $config): OidcAccessPolicy
    {
        $environment = $this->createMock(Environment::class);
        $environment->method('get')->willReturnCallback(
            fn (string $key, $default = null) => $config[$key] ?? $default
        );

        return new OidcAccessPolicy($environment);
    }

    public static function identityProvider(): array
    {
        $verified = ['email_verified' => true, 'hd' => self::ALLOWED];

        return [
            'conta legítima do workspace' => [$verified, 'daniel@digitalcollege.com.br', true],
            'caixa alta no e-mail e no hd' => [
                ['email_verified' => true, 'hd' => 'DigitalCollege.com.BR'],
                'Nome.Sobrenome@DigitalCollege.com.BR',
                true,
            ],
            'domínio parecido não entra' => [
                ['email_verified' => true, 'hd' => 'fakedigitalcollege.com.br'],
                'x@fakedigitalcollege.com.br',
                false,
            ],
            'domínio permitido como prefixo de outro' => [$verified, 'x@digitalcollege.com.br.evil.com', false],
            'conta pessoal' => [['email_verified' => true], 'x@gmail.com', false],
            'sem claim hd' => [['email_verified' => true], 'daniel@digitalcollege.com.br', false],
            'hd de outro tenant' => [
                ['email_verified' => true, 'hd' => 'outracorp.com'],
                'daniel@digitalcollege.com.br',
                false,
            ],
            'hd vazio' => [['email_verified' => true, 'hd' => '   '], 'daniel@digitalcollege.com.br', false],
            'email_verified falso' => [
                ['email_verified' => false, 'hd' => self::ALLOWED],
                'daniel@digitalcollege.com.br',
                false,
            ],
            'email_verified ausente' => [['hd' => self::ALLOWED], 'daniel@digitalcollege.com.br', false],
            'e-mail malformado' => [$verified, 'a@b@digitalcollege.com.br', false],
        ];
    }

    /**
     * @dataProvider identityProvider
     */
    public function test_identity_is_authorized_only_when_every_rule_passes(
        array $claims,
        string $email,
        bool $shouldAllow
    ): void {
        $policy = $this->policy($this->strictConfig());

        if (! $shouldAllow) {
            $this->expectException(OidcAccessDeniedException::class);
        }

        $policy->assertAllowed($claims, $email);

        if ($shouldAllow) {
            // Sem exceção é o resultado esperado; a asserção existe para o teste
            // não ser marcado como "risky" por não asserir nada.
            $this->assertTrue(true);
        }
    }

    /**
     * Combinação perigosa: sem allowlist, o auto-provisionamento criaria conta para
     * qualquer identidade que o provedor autenticasse. Precisa negar, não liberar.
     */
    public function test_denies_everything_when_autoprovisioning_has_no_allowlist(): void
    {
        $policy = $this->policy([
            'oidcAllowedEmailDomains' => '',
            'oidcCreateUser' => true,
        ]);

        $this->expectException(OidcAccessDeniedException::class);

        $policy->assertAllowed(['email_verified' => true], 'daniel@digitalcollege.com.br');
    }

    /**
     * Sem allowlist e sem criação automática, mantém o comportamento do upstream:
     * só entra quem já tem conta, e a política não opina.
     */
    public function test_keeps_upstream_behaviour_without_allowlist_and_without_autoprovisioning(): void
    {
        $policy = $this->policy([
            'oidcAllowedEmailDomains' => '',
            'oidcCreateUser' => false,
        ]);

        $policy->assertAllowed([], 'qualquer@lugar.com');

        $this->assertTrue(true);
    }

    public function test_accepts_any_domain_in_a_multi_domain_allowlist(): void
    {
        $policy = $this->policy([
            'oidcAllowedEmailDomains' => 'outra.com, '.self::ALLOWED,
            'oidcRequireHostedDomain' => true,
            'oidcCreateUser' => true,
        ]);

        $policy->assertAllowed(['email_verified' => true, 'hd' => self::ALLOWED], 'daniel@digitalcollege.com.br');

        $this->assertTrue(true);
    }

    /**
     * O motivo específico da recusa fica no log; o usuário recebe chave genérica.
     * Contar qual regra falhou daria a um atacante um oráculo sobre a allowlist.
     */
    public function test_denial_exposes_only_a_generic_translation_key(): void
    {
        $policy = $this->policy($this->strictConfig());

        try {
            $policy->assertAllowed(['email_verified' => true], 'x@gmail.com');
            $this->fail('Esperava OidcAccessDeniedException');
        } catch (OidcAccessDeniedException $e) {
            $this->assertSame('oidc.error.domainNotAllowed', $e->getTranslationKey());
        }
    }
}
