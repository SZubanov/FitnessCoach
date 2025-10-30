<?php

namespace App\Telegram\Callbacks\FatSecret;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Logout FatSecret Callback Handler
 *
 * Logs out from FatSecret by removing authorization tokens.
 * After logout, synchronization features will no longer be available.
 * Callback name: 'logoutFromFatSecret'
 */
class LogoutFatSecretCallback implements CallbackHandler
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
     * Logs out from FatSecret and displays confirmation message.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        if (!$user) {
            $chat->edit($messageId)
                ->markdown('❌ Аккаунт не привязан.')
                ->keyboard($this->keyboardFactory->fatSecretBack())
                ->send();
            return;
        }

        // Use the service from app/Telegram/Services (the one with logout method)
        $telegramFatSecretService = app(\App\Telegram\Services\TelegramFatSecretService::class);
        $telegramFatSecretService->logout($chat->chat_id);

        $message = "🚪 **Отключение от FatSecret**\n\n" .
            "Ваш аккаунт был отключен от FatSecret\n\n" .
            "❌ Функции синхронизации больше не доступны\n";

        $chat->edit($messageId)
            ->markdown($message)
            ->keyboard($this->keyboardFactory->fatSecretBack())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'logoutFromFatSecret';
    }
}
