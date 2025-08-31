<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;

class FatSecretMiddleware
{
    public function __invoke(Nutgram $bot, $next)
    {
        // Check if FatSecret is connected (replace with actual database check)
        $isConnected = $this->checkFatSecretConnection($bot->userId());
        
        if (!$isConnected) {
            $bot->sendMessage(
                "❌ **FatSecret не подключен**\n\n" .
                "Для синхронизации необходимо подключить FatSecret.\n" .
                "Перейдите в Настройки → FatSecret → Подключить"
            );
            return;
        }
        
        $next($bot);
    }
    
    private function checkFatSecretConnection(int $userId): bool
    {
        // Replace with actual database check
        // return FatSecretToken::where('user_id', $userId)->exists();
        return false; // Temporary - require setup for sync features
    }
}