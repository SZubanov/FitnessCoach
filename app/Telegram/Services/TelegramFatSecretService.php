<?php

namespace App\Telegram\Services;

use App\Contracts\Actions\Users\FatSecretLogoutInterface;
use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\FatSecret\Exceptions\FatSecretException;
//use App\FatSecret\FatSecretFacadeInterface;

readonly class TelegramFatSecretService
{
    public function __construct(
        private FatSecretLogoutInterface $fatSecretLogout,
//        private FatSecretFacadeInterface          $fatSecretFacade,
        private  GetUserByTelegramIdInterface $getUserByTelegramId,
    ) {

    }

    public function isUserFatSecretAuthorized($telegramUserId)
    {
        $user = ($this->getUserByTelegramId)($telegramUserId);
        return $user->isFatSecretAuthorized();
    }

    public function logout($telegramUserId): void
    {
        $user = ($this->getUserByTelegramId)($telegramUserId);
        ($this->fatSecretLogout)($user);
    }

    /**
     * @throws FatSecretException
     */
    public function getRequestTokenUrl(): string
    {
        return 'link';
//        return $this->fatSecretFacade->getRequestToken();
    }
}
