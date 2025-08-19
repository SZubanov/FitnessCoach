<?php

namespace App\Services\Telegram;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\User\User as TelegramUser;

class TelegramUserService
{
    public function findOrCreateUser(TelegramUser $telegramUser): User
    {
        $user = User::where('telegram_id', $telegramUser->id)->first();

        if (!$user) {
            $user = User::create([
                'name' => $telegramUser->first_name . ($telegramUser->last_name ? ' ' . $telegramUser->last_name : ''),
                'email' => $telegramUser->id . '@telegram.local',
                'password' => bcrypt(str()->random(32)),
                'telegram_id' => $telegramUser->id,
                'telegram_username' => $telegramUser->username,
                'timezone' => 'Europe/Moscow'
            ]);
        } else {
            $user->update([
                'telegram_username' => $telegramUser->username,
            ]);
        }

        return $user;
    }

    public function getCurrentUser(Nutgram $bot): ?User
    {
        return User::where('telegram_id', $bot->userId())->first();
    }

    public function linkExistingUser(User $user, TelegramUser $telegramUser): User
    {
        $user->update([
            'telegram_id' => $telegramUser->id,
            'telegram_username' => $telegramUser->username,
        ]);

        return $user;
    }
}