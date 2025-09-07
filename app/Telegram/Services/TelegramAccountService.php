<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Contracts\Actions\Telegram\GenerateLinkAccountCodeInterface;
use App\Contracts\Actions\Telegram\UnlinkTelegramAccountInterface;
use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\Telegram\Exceptions\AbstractTelegramBotException;
use App\Telegram\Exceptions\UnlinkTelegramAccountException;
use App\Telegram\Exceptions\UserNotFoundException;

readonly class TelegramAccountService
{
    public function __construct(
        private GetUserByTelegramIdInterface     $getUserByTelegramId,
        private UnlinkTelegramAccountInterface   $unlinkTelegramAccount,
        private GenerateLinkAccountCodeInterface $generateLinkAccountCode,
    ) {
    }

    public function generateLinkAccountCode(int $telegramId): string
    {
        return ($this->generateLinkAccountCode)($telegramId);
    }

    public function checkLinkAccountStatus(int $telegramUserId): bool
    {
       return ($this->getUserByTelegramId)($telegramUserId) !== null;
    }

    /**
     * @param int $telegramUserId
     * @return void
     * @throws AbstractTelegramBotException
     */
    public function removeLinkAccount(int $telegramUserId): void
    {
        $user = ($this->getUserByTelegramId)($telegramUserId);
        if ($user === null) {
            throw new UserNotFoundException();
        }

        $result = ($this->unlinkTelegramAccount)($user);
        if ($result === false) {
            throw new UnlinkTelegramAccountException();
        }
    }
}
