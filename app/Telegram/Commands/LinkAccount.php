<?php

namespace App\Telegram\Commands;

use App\Services\Telegram\TelegramUserService;
use App\Telegram\Services\TelegramMessageService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Handlers\Type\Command;

class LinkAccount extends Command
{
    protected string $command = 'link';

    protected ?string $description = 'Command send code for linkin Telegram to an account of a user.';

    public function handle(Nutgram $bot, TelegramUserService $userService, TelegramMessageService $telegramMessageService): void
    {
        $existingUser = $userService->getCurrentUser($bot);

        if ($existingUser) {
            $telegramMessageService->sendMessageLinkExistingAccount($bot, $existingUser);
        } else {
            $telegramMessageService->sendMessageLinkNewAccount($bot);
        }
    }
}
