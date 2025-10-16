<?php

namespace App\Telegram\Middleware\Contracts;

use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Interface for Telegram middleware (Chain of Responsibility pattern)
 *
 * Middleware can intercept and modify the flow before reaching
 * the actual handler (e.g., for authentication, authorization, rate limiting)
 */
interface TelegramMiddleware
{
    /**
     * Handle the middleware logic
     *
     * @param TelegraphChat $chat Telegram chat
     * @param Closure $next Next middleware or handler in the chain
     * @return mixed Result from the chain (or null to stop)
     */
    public function handle(TelegraphChat $chat, Closure $next): mixed;
}