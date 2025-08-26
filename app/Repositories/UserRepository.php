<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Dto\User\UserDto;
use App\Models\User;
use DB;

class UserRepository
{
    public function getUserById(int $id): UserDto
    {
        $user = DB::table('users')
            ->find(
                $id,
                [
                    'id',
                    'name',
                    'email',
                    'default_measure_system',
                    DB::raw('COALESCE(oauth_token_secret IS NOT NULL AND oauth_token IS NOT NULL, false) as is_fat_secret_active')
                ]
            );

        return new UserDto(
            $user->id,
            $user->name,
            $user->email,
            $user->default_measure_system,
            $user->is_fat_secret_active
        );
    }

    public function getUserByTelegramId(int $id): ?User
    {
        return User::where('telegram_id', $id)->first();
    }
}
