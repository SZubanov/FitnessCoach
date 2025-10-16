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
     * If linked, passes the User instance to the next middleware/handler.
     *
     * @param TelegraphChat $chat Telegram chat
     * @param Closure $next Next middleware or handler in the chain
     * @return mixed User instance or null if stopped
     */
    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        if (!$user) {
            $this->sendNotLinkedMessage($chat);
            return null;
        }

        // Pass user to next middleware/handler
        return $next($user);
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