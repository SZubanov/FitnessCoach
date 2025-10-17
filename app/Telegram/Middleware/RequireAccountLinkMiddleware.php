<?php

namespace App\Telegram\Middleware;

use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\Contracts\TelegramMiddleware;
use App\Telegram\Services\TelegramUserService;
use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Require Account Link Middleware
 *
 * Guards against unauthorized access by requiring the Telegram account
 * to be linked to a FitnessCoach user account.
 *
 * If account is not linked, sends an error message and stops execution.
 */
class RequireAccountLinkMiddleware implements TelegramMiddleware
{
    /**
     * Create middleware instance
     *
     * @param TelegramUserService $userService Service to check user linking
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly TelegramUserService $userService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the middleware logic
     *
     * Checks if the Telegram user is linked to a FitnessCoach account.
     * If not linked, sends error message and returns null to stop execution.
     * If linked, stores user on chat and passes chat to next middleware.
     *
     * @param TelegraphChat $chat Telegram chat
     * @param Closure $next Next middleware or handler in the chain
     * @return mixed Result from next middleware/handler or null if stopped
     */
    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        if (!$user) {
            $this->sendNotLinkedMessage($chat);
            return null;
        }

        // Store user on chat for downstream middleware access
        // This is a runtime property, not persisted to database
        $chat->_authenticatedUser = $user;

        // Pass chat to next middleware/handler (maintaining interface contract)
        return $next($chat);
    }

    /**
     * Send "not linked" error message
     *
     * @param TelegraphChat $chat
     * @return void
     */
    private function sendNotLinkedMessage(TelegraphChat $chat): void
    {
        $chat->message('❌ Аккаунт не привязан.\n\nИспользуйте /account для привязки аккаунта.')
            ->keyboard($this->keyboardFactory->accountLinking())
            ->send();
    }
}