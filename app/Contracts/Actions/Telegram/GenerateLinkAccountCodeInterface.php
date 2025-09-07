<?php

namespace App\Contracts\Actions\Telegram;

interface GenerateLinkAccountCodeInterface
{
    public function __invoke(int $telegramUserId): string;
}
