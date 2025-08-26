<?php

namespace App\Telegram\Commands;

use App\Telegram\Services\TelegramMessageService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Handlers\Type\Command;

class StartCommand extends Command
{
    protected string $command = 'start';

    protected ?string $description = 'Start conversation with bot';

    public function handle(Nutgram $bot, TelegramMessageService $telegramMessageService): void
    {
        $telegramMessageService->sendMessageWelcome($bot);
    }
}
