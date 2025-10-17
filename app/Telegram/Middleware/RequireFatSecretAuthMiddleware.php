<?php

namespace App\Telegram\Middleware;

use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\Contracts\TelegramMiddleware;
use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Require FatSecret Auth Middleware
 *
 * Guards against unauthorized access by requiring the user to have
 * authorized FatSecret integration.
 *
 * This middleware should typically run AFTER RequireAccountLinkMiddleware
 * since it depends on having a User instance.
 *
 * If FatSecret is not authorized, sends an error message and stops execution.
 */
class RequireFatSecretAuthMiddleware implements TelegramMiddleware
{
    /**
     * Create middleware instance
     *
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the middleware logic
     *
     * Checks if the user has authorized FatSecret.
     * Gets the User instance from the chat's _authenticatedUser property
     * set by RequireAccountLinkMiddleware.
     *
     * @param TelegraphChat $chat Telegram chat with _authenticatedUser property
     * @param Closure $next Next middleware or handler in the chain
     * @return mixed Result from next middleware/handler or null if stopped
     */
    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        // Get the authenticated user stored by RequireAccountLinkMiddleware
        $user = $chat->_authenticatedUser ?? null;

        if (!$user || !$user->isFatSecretAuthorized()) {
            $this->sendNotAuthorizedMessage($chat);
            return null;
        }

        // Pass chat to next middleware/handler (maintaining interface contract)
        return $next($chat);
    }

    /**
     * Send "not authorized" error message
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    private function sendNotAuthorizedMessage(TelegraphChat $chat): void
    {
        $chat->message('❌ FatSecret не подключен.\n\nИспользуйте /fatsecret для подключения.')
            ->keyboard($this->keyboardFactory->fatSecretMenu())
            ->send();
    }
}