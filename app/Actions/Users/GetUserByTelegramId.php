<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\Models\User;

readonly class GetUserByTelegramId implements GetUserByTelegramIdInterface
{
    public function __invoke(int $telegramUserId): ?User
    {
        return User::where('telegram_id', $telegramUserId)->first();
    }
}
