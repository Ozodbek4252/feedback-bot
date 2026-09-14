<?php

namespace App\Services\Telegram;

use App\Models\FeedbackMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeedbackForwarder
{
    private const CONTENT_TYPES = [
        'text', 'photo', 'document', 'voice', 'video',
        'video_note', 'audio', 'sticker', 'animation', 'location', 'contact',
    ];

    public function __construct(
        private readonly string $botSlug,
        private readonly array $botConfig,
        private readonly TelegramApi $api,
    ) {
    }

    public function handle(array $message): void
    {
        $feedback = FeedbackMessage::firstOrNew([
            'bot' => $this->botSlug,
            'telegram_message_id' => $message['message_id'],
        ]);

        if ($feedback->exists) {
            return;
        }

        $from = $message['from'] ?? [];

        $feedback->fill([
            'telegram_chat_id' => $message['chat']['id'],
            'telegram_user_id' => $from['id'] ?? 0,
            'telegram_username' => $from['username'] ?? null,
            'telegram_first_name' => $from['first_name'] ?? null,
            'telegram_last_name' => $from['last_name'] ?? null,
            'type' => $this->resolveType($message),
            'text' => $message['text'] ?? $message['caption'] ?? null,
            'payload' => $message,
        ])->save();

        $this->forward($feedback);
    }

    private function forward(FeedbackMessage $feedback): void
    {
        $chatId = $this->botConfig['chat_id'] ?? null;

        if (empty($chatId)) {
            Log::warning("Telegram bot [{$this->botSlug}] has no target chat_id configured; message stored but not forwarded.", [
                'feedback_id' => $feedback->id,
            ]);

            return;
        }

        try {
            $forwarded = $this->api->forwardMessage(
                $chatId,
                $feedback->telegram_chat_id,
                $feedback->telegram_message_id,
            );

            $feedback->update([
                'forwarded_message_id' => $forwarded['message_id'] ?? null,
                'forwarded_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to forward Telegram feedback message [{$this->botSlug}] #{$feedback->id}: {$e->getMessage()}");
        }
    }

    private function resolveType(array $message): string
    {
        foreach (self::CONTENT_TYPES as $type) {
            if (isset($message[$type])) {
                return $type;
            }
        }

        return 'unknown';
    }
}
