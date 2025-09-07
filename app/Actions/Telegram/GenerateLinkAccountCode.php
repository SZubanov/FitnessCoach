<?php

namespace App\Actions\Telegram;

use App\Contracts\Actions\Telegram\GenerateLinkAccountCodeInterface;
use App\Exceptions\CreateTelegramAccountLinkCodeException;

class GenerateLinkAccountCode implements GenerateLinkAccountCodeInterface
{
    private const TELEGRAM_LINK_CODE_CACHE_KEY = 'telegram_link_code',
        TELEGRAM_LINK_CODE_CACHE_TTL_SEC = 900;

    public function __invoke(int $telegramUserId): string
    {
        $code = $this->generateCode($telegramUserId);
        $cacheKey = self::TELEGRAM_LINK_CODE_CACHE_KEY . "_{$code}";

        $result = \Cache::put($cacheKey, $telegramUserId, self::TELEGRAM_LINK_CODE_CACHE_TTL_SEC);

        if (!$result) {
            throw new CreateTelegramAccountLinkCodeException();
        }

        return $code;
    }

    private function generateCode(int $telegramUserId): string
    {
        return strtoupper(substr(md5($telegramUserId . time()), 0, 8));
    }
}
