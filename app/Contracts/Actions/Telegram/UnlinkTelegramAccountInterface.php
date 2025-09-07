<?php

namespace App\Contracts\Actions\Telegram;

use App\Models\User;

interface UnlinkTelegramAccountInterface
{
    public function __invoke(User $user): bool;
}
