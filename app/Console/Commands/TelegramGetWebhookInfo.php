<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class TelegramGetWebhookInfo extends Command
{
    protected $signature = 'telegram:info';

    protected $description = 'Get Telegram webhook information';

    public function handle(): int
    {
        try {
            // Create bot instance directly to avoid service provider issues during setup
            $bot = new \SergiX44\Nutgram\Nutgram(config('nutgram.token'));
            $info = $bot->getWebhookInfo();

            $this->info('Webhook Information:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['URL', $info->url ?? 'Not set'],
                    ['Has Custom Certificate', $info->has_custom_certificate ? 'Yes' : 'No'],
                    ['Pending Update Count', $info->pending_update_count ?? 0],
                    ['IP Address', $info->ip_address ?? 'Not set'],
                    ['Last Error Date', $info->last_error_date ? date('Y-m-d H:i:s', $info->last_error_date) : 'None'],
                    ['Last Error Message', $info->last_error_message ?? 'None'],
                    ['Max Connections', $info->max_connections ?? 'Default'],
                    ['Allowed Updates', !empty($info->allowed_updates) ? implode(', ', $info->allowed_updates) : 'All'],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error getting webhook info: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
