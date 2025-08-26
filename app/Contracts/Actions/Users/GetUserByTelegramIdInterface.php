<?php

namespace App\Contracts\Actions\Users;

use App\Models\User;

interface GetUserByTelegramIdInterface
{
    public function __invoke(int $telegramUserId): ?User;
}
