<?php

namespace App\Telegram\Commands;

use App\Telegram\Services\TelegramMessageService;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class HelpCommand extends Command
{
    protected string $command = 'help';

    protected ?string $description = 'Command send help message.';

    public function handle(Nutgram $bot, TelegramMessageService $telegramMessageService): void
    {
        $telegramMessageService->sendMessageHelp($bot);
    }
}
