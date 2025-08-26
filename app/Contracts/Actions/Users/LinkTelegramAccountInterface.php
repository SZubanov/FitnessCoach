<?php

namespace App\Contracts\Actions\Users;

use App\Models\User;

interface LinkTelegramAccountInterface
{
    public function __invoke(User $user, int $telegramUserId, string $telegramUsername): bool;
}
