<?php

namespace Leantime\Domain\Supportchat\Services;

use GuzzleHttp\Client;

/**
 * Proxy de suporte via OpenAI Assistants API.
 *
 * A chave e o assistant ficam SEMPRE no servidor (env LEAN_OPENAI_API_KEY /
 * LEAN_SUPPORTCHAT_ASSISTANT_ID); o browser conversa apenas com o endpoint
 * interno autenticado. O contexto da tela do usuário entra por execução via
 * additional_instructions, preservando as instruções treinadas do assistant.
 */
class Supportchat
{
    private const API_BASE = 'https://api.openai.com/v1';

    private const RUN_TIMEOUT_SECONDS = 25;

    private const MAX_MESSAGE_CHARS = 2000;

    private const MAX_CONTEXT_CHARS = 4000;

    public function __construct(private Client $httpClient) {}

    /**
     * O chat só fica disponível quando key e assistant estão configurados.
     *
     * @api
     */
    public function isConfigured(): bool
    {
        return ! empty(env('LEAN_OPENAI_API_KEY'))
            && ! empty(env('LEAN_SUPPORTCHAT_ASSISTANT_ID'))
            && filter_var(env('LEAN_SUPPORTCHAT_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Envia uma mensagem do usuário ao assistant e retorna a resposta.
     *
     * @param  string  $message  Pergunta do usuário.
     * @param  string|null  $threadId  Thread existente da conversa (null inicia uma nova).
     * @param  string  $screenContext  Extrato textual da tela atual (pode ser vazio).
     * @return array{reply: string, threadId: string}
     *
     * @throws \RuntimeException Quando a API falha ou o run não conclui no tempo limite.
     */
    public function sendMessage(string $message, ?string $threadId, string $screenContext = ''): array
    {
        $message = mb_substr(trim($message), 0, self::MAX_MESSAGE_CHARS);

        if ($threadId !== null && ! preg_match('/^thread_[A-Za-z0-9]+$/', $threadId)) {
            $threadId = null;
        }

        if ($threadId === null) {
            $thread = $this->request('POST', '/threads');
            $threadId = $thread['id'];
        }

        $this->request('POST', "/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $message,
        ]);

        $run = $this->request('POST', "/threads/{$threadId}/runs", [
            'assistant_id' => env('LEAN_SUPPORTCHAT_ASSISTANT_ID'),
            'additional_instructions' => $this->buildRunInstructions($screenContext),
        ]);

        $this->waitForRun($threadId, $run['id']);

        $messages = $this->request('GET', "/threads/{$threadId}/messages?limit=1&order=desc");
        $reply = $messages['data'][0]['content'][0]['text']['value'] ?? '';

        if ($reply === '') {
            throw new \RuntimeException('Assistant returned an empty reply');
        }

        return ['reply' => $reply, 'threadId' => $threadId];
    }

    /**
     * Instruções por execução: idioma + contexto da tela (quando habilitado).
     */
    private function buildRunInstructions(string $screenContext): string
    {
        $brand = env('LEAN_SITENAME', 'Time');

        $instructions = 'O usuário está logado no '.$brand.', plataforma web de gestão estratégica de projetos '
            .'(projetos, tarefas, kanban, metas, marcos, planilhas de horas e calendário). '
            .'Responda sempre em português do Brasil e seja conciso.';

        $contextEnabled = filter_var(env('LEAN_SUPPORTCHAT_SCREEN_CONTEXT', true), FILTER_VALIDATE_BOOLEAN);

        if ($contextEnabled && trim($screenContext) !== '') {
            $instructions .= "\n\nConteúdo da tela que o usuário está vendo agora (parcial, use quando "
                ."for relevante para a pergunta):\n"
                .mb_substr(trim($screenContext), 0, self::MAX_CONTEXT_CHARS);
        }

        return $instructions;
    }

    /**
     * Aguarda o run concluir (poll), respeitando o tempo limite do request PHP.
     */
    private function waitForRun(string $threadId, string $runId): void
    {
        $deadline = time() + self::RUN_TIMEOUT_SECONDS;

        do {
            usleep(800_000);
            $run = $this->request('GET', "/threads/{$threadId}/runs/{$runId}");

            if ($run['status'] === 'completed') {
                return;
            }

            if (in_array($run['status'], ['failed', 'cancelled', 'expired', 'incomplete'], true)) {
                throw new \RuntimeException('Assistant run ended with status: '.$run['status']);
            }
        } while (time() < $deadline);

        throw new \RuntimeException('Assistant run timed out');
    }

    /**
     * Chamada autenticada à OpenAI API com os headers da Assistants v2.
     */
    private function request(string $method, string $path, ?array $json = null): array
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.env('LEAN_OPENAI_API_KEY'),
                'OpenAI-Beta' => 'assistants=v2',
            ],
            'timeout' => 20,
        ];

        if ($json !== null) {
            $options['json'] = $json;
        }

        $response = $this->httpClient->request($method, self::API_BASE.$path, $options);

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}
