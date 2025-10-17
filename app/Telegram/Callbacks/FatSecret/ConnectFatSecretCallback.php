<?php

namespace App\Telegram\Callbacks\FatSecret;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Connect FatSecret Callback Handler
 *
 * Initiates FatSecret OAuth flow by generating authorization URL.
 * Requires account linking middleware.
 * User clicks the link to authorize with FatSecret.
 * Callback name: 'connectFatSecret'
 */
class ConnectFatSecretCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param TelegramUserService $userService Service to manage Telegram users
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly TelegramUserService $userService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the callback execution
     *
     * Checks account linking via middleware, then initiates OAuth flow.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        // Build middleware pipeline for authorization check
        $pipeline = new MiddlewarePipeline([
            new RequireAccountLinkMiddleware($this->userService, $this->keyboardFactory),
        ]);

        // Execute through pipeline
        $pipeline->through($chat, function ($chat) use ($messageId) {
            $this->initiateOAuthFlow($chat, $messageId);
        });
    }

    /**
     * Initiate FatSecret OAuth flow
     *
     * Generates OAuth URL and displays it to the user.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    private function initiateOAuthFlow(TelegraphChat $chat, ?int $messageId): void
    {
        // Get authenticated user from middleware runtime property
        $user = $chat->_authenticatedUser;

        try {
            // Use the service from app/Services/Telegram (the one with initiateOAuthForTelegram)
            $telegramFatSecretService = app(\App\Services\Telegram\TelegramFatSecretService::class);
            $oauthUrl = $telegramFatSecretService->initiateOAuthForTelegram($user);

            $message = "🔗 **Подключение FatSecret**\n\n" .
                "Для подключения к FatSecret нажмите на ссылку ниже:\n\n" .
                "[Подключить FatSecret]({$oauthUrl})\n\n" .
                "После авторизации вы будете автоматически перенаправлены обратно в бот";

            $chat->edit($messageId)
                ->html($message)
                ->keyboard($this->keyboardFactory->fatSecretBack())
                ->send();
        } catch (\Exception $e) {
            $chat->edit($messageId)
                ->html('❌ Ошибка при создании ссылки для подключения FatSecret. Попробуйте позже.')
                ->keyboard($this->keyboardFactory->fatSecretBack())
                ->send();
        }
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'connectFatSecret';
    }
}