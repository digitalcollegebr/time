<?php

namespace Leantime\Domain\Supportchat\Controllers;

use Illuminate\Support\Facades\Log;
use Leantime\Core\Controller\Controller;
use Leantime\Domain\Supportchat\Services\Supportchat as SupportchatService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /supportchat/message — endpoint interno do widget de suporte (Jarbas).
 *
 * Roda atrás do AuthCheck (somente usuários logados). O browser nunca vê a
 * chave da OpenAI: este endpoint é o único intermediário.
 */
class Message extends Controller
{
    private SupportchatService $chatService;

    public function init(SupportchatService $chatService): void
    {
        $this->chatService = $chatService;
    }

    public function post(array $params): Response
    {
        if (! session()->exists('userdata.id')) {
            return new JsonResponse(['error' => 'Não autenticado'], 401);
        }

        if (! $this->chatService->isConfigured()) {
            return new JsonResponse(['error' => 'Chat de suporte não configurado'], 503);
        }

        $body = json_decode($this->incomingRequest->getContent() ?: '', true) ?? [];

        $message = trim((string) ($body['message'] ?? ''));
        if ($message === '') {
            return new JsonResponse(['error' => 'Mensagem vazia'], 422);
        }

        try {
            $result = $this->chatService->sendMessage(
                $message,
                isset($body['threadId']) ? (string) $body['threadId'] : null,
                (string) ($body['screenContext'] ?? ''),
            );

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            Log::error($e);

            return new JsonResponse(
                ['error' => 'Não consegui falar com o assistente agora. Tente novamente em instantes.'],
                502,
            );
        }
    }
}
