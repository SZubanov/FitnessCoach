<?php

namespace App\Telegram\Commands;

use App\Services\Telegram\TelegramUserService;
use App\Telegram\Services\TelegramAccountService;
use App\Telegram\Services\TelegramMessageService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Handlers\Type\Command;

class LinkAccountCommand extends Command
{
    protected string $command = 'link';

    protected ?string $description = 'Command send code for linkin Telegram to an account of a user.';

    public function handle(
        Nutgram $bot,
        TelegramUserService $userService,
        TelegramMessageService $telegramMessageService,
        TelegramAccountService $telegramAccountService,
    ): void {
        $user = $userService->getCurrentUser($bot);

        if ($user) {
            $telegramMessageService->sendMessageLinkExistingAccount($bot, $user);
        } else {
            $link = $telegramAccountService->generateLinkAccountCode($bot->userId());
            $telegramMessageService->sendMessageLinkNewAccount($bot, $link);
        }
    }
}
