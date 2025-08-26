<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\LinkTelegramAccountInterface;
use App\Models\User;

class LinkTelegramAccount implements LinkTelegramAccountInterface
{
    public function __invoke(User $user, int $telegramUserId, string $telegramUsername): bool
    {
       return $user->update([
            'telegram_id' => $telegramUserId,
            'telegram_username' => $telegramUsername,
        ]);
    }
}
