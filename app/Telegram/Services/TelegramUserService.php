<?php

namespace App\Telegram\Services;

use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\Models\User;

class TelegramUserService
{
    public function __construct(readonly private GetUserByTelegramIdInterface $getUserByTelegramId)
    {

    }

    public function getCurrentUser(int $userId): ?User
    {
        return ($this->getUserByTelegramId)($userId);
    }
}
