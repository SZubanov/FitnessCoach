<?php

namespace App\Services\Telegram;

use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;
class TelegramUserService
{
    public function __construct(readonly private GetUserByTelegramIdInterface $getUserByTelegramId)
    {

    }

    public function getCurrentUser(Nutgram $bot): ?User
    {
        return ($this->getUserByTelegramId)($bot->userId());
    }
}
