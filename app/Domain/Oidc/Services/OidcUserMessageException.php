<?php

namespace Leantime\Domain\Oidc\Services;

/**
 * Erro de OIDC cuja mensagem JÁ está traduzida e é segura para exibir ao usuário.
 *
 * Serve para separar as duas naturezas de falha no fluxo de login. O callback
 * exibia a mensagem de qualquer exceção capturada, o que fazia um erro de banco
 * imprimir o SQL e os bindings na tela de login. Com este marcador, só o que foi
 * escrito para ser lido por gente chega até a tela; o resto vira mensagem
 * genérica e vai inteiro para o log.
 */
class OidcUserMessageException extends \RuntimeException {}
