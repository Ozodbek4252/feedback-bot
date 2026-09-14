<?php

namespace App\Console\Commands;

use App\Models\TelegramPollState;
use App\Services\Telegram\FeedbackForwarder;
use App\Services\Telegram\TelegramApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {bot : The bot slug from config/telegram.php}';

    protected $description = 'Long-poll a Telegram bot for new messages and forward them to its group';

    private bool $shouldStop = false;

    public function handle(): int
    {
        $slug = $this->argument('bot');
        $config = config("telegram.bots.{$slug}");

        if (! $config || empty($config['token'])) {
            $this->error("No Telegram bot configured for slug [{$slug}]. Check config/telegram.php and your .env.");

            return self::FAILURE;
        }

        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () {
                $this->shouldStop = true;
            });
            pcntl_signal(SIGINT, function () {
                $this->shouldStop = true;
            });
        }

        $api = new TelegramApi($config['token']);
        $forwarder = new FeedbackForwarder($slug, $config, $api);
        $state = TelegramPollState::firstOrCreate(['bot' => $slug]);

        $this->info("Polling Telegram bot [{$slug}] starting from update_id {$state->last_update_id}...");

        while (! $this->shouldStop) {
            try {
                $updates = $api->getUpdates($state->last_update_id + 1, timeout: 30);
            } catch (Throwable $e) {
                Log::error("Telegram getUpdates failed for bot [{$slug}]: {$e->getMessage()}");
                sleep(5);

                continue;
            }

            foreach ($updates as $update) {
                if (isset($update['message'])) {
                    try {
                        $forwarder->handle($update['message']);
                    } catch (Throwable $e) {
                        Log::error("Failed to process Telegram update for bot [{$slug}]: {$e->getMessage()}", [
                            'update_id' => $update['update_id'],
                        ]);
                    }
                }

                $state->update(['last_update_id' => $update['update_id']]);
            }
        }

        $this->info("Stopping Telegram poller for bot [{$slug}].");

        return self::SUCCESS;
    }
}
