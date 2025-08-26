<?php

namespace App\Telegram\Services;

class TelegramAccountService
{
    public function generateLinkAccountCode(int $telegramId): string
    {
        $code = strtoupper(substr(md5($telegramId . time()), 0, 8));

        // Store the link code in cache for 15 minutes
        $cacheKey = "telegram_link_code_{$code}";
        \Cache::put($cacheKey, $telegramId, now()->addMinutes(15));

        return $code;
    }
}
