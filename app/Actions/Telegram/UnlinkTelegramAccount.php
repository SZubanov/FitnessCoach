<?php

namespace App\Actions\Telegram;

use App\Contracts\Actions\Telegram\UnlinkTelegramAccountInterface;
use App\Models\User;

class UnlinkTelegramAccount implements UnlinkTelegramAccountInterface
{
    public function __invoke(User $user): bool
    {
       return $user->update([
            'telegram_id' => null,
            'telegram_username' => null,
        ]);
    }
}
