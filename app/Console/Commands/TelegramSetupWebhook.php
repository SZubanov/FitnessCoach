<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class TelegramSetupWebhook extends Command
{
    protected $signature = 'telegram:webhook {--url= : Webhook URL}';
    
    protected $description = 'Set up Telegram webhook';

    public function handle(): int
    {
        $url = $this->option('url') ?? config('nutgram.webhook_url');
        
        if (!$url) {
            $this->error('Webhook URL is not set. Use --url option or set TELEGRAM_WEBHOOK_URL in .env');
            return self::FAILURE;
        }

        try {
            // Create bot instance directly to avoid service provider issues during setup
            $bot = new \SergiX44\Nutgram\Nutgram(config('nutgram.token'));
            $result = $bot->setWebhook($url);
            
            if ($result) {
                $this->info("Webhook successfully set to: {$url}");
                return self::SUCCESS;
            } else {
                $this->error('Failed to set webhook');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Error setting webhook: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}