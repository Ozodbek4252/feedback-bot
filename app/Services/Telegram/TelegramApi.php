<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;

class TelegramApi
{
    public function __construct(private readonly string $token)
    {
    }

    public function getUpdates(int $offset, int $timeout = 30): array
    {
        $response = Http::timeout($timeout + 10)
            ->post($this->endpoint('getUpdates'), [
                'offset' => $offset,
                'timeout' => $timeout,
            ]);

        $response->throw();

        return $response->json('result', []);
    }

    public function sendMessage(int|string $chatId, string $text): array
    {
        $response = Http::timeout(15)
            ->post($this->endpoint('sendMessage'), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

        $response->throw();

        return $response->json('result', []);
    }

    public function forwardMessage(int|string $chatId, int|string $fromChatId, int $messageId): array
    {
        $response = Http::timeout(15)
            ->post($this->endpoint('forwardMessage'), [
                'chat_id' => $chatId,
                'from_chat_id' => $fromChatId,
                'message_id' => $messageId,
            ]);

        $response->throw();

        return $response->json('result', []);
    }

    public function copyMessage(int|string $chatId, int|string $fromChatId, int $messageId): array
    {
        $response = Http::timeout(15)
            ->post($this->endpoint('copyMessage'), [
                'chat_id' => $chatId,
                'from_chat_id' => $fromChatId,
                'message_id' => $messageId,
            ]);

        $response->throw();

        return $response->json('result', []);
    }

    private function endpoint(string $method): string
    {
        return "https://api.telegram.org/bot{$this->token}/{$method}";
    }
}
