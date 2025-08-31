<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;

class AccountLinkMiddleware
{
    public function __invoke(Nutgram $bot, $next)
    {
        // Check if user account is linked (replace with actual database check)
        $isLinked = $this->checkAccountLink($bot->userId());
        
        if (!$isLinked) {
            $bot->sendMessage(
                "❌ **Аккаунт не привязан**\n\n" .
                "Для использования этой функции необходимо привязать аккаунт.\n" .
                "Перейдите в Настройки → Привязка аккаунта"
            );
            return;
        }
        
        $next($bot);
    }
    
    private function checkAccountLink(int $userId): bool
    {
        // Replace with actual database check
        // return User::where('telegram_id', $userId)->exists();
        return true; // Temporary - always allow for testing
    }
}