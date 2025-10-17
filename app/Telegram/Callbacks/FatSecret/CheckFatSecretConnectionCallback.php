<?php

namespace App\Telegram\Callbacks\FatSecret;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Check FatSecret Connection Callback Handler
 *
 * Checks and displays the FatSecret authorization status.
 * Shows whether the user is authorized with FatSecret API.
 * Callback name: 'checkFatSecretConnection'
 */
class CheckFatSecretConnectionCallback implements CallbackHandler
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
     * Checks FatSecret authorization status and displays result.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);
        $isAuthorized = $user && $user->isFatSecretAuthorized();

        $statusMessage = $isAuthorized
            ? "✅ **Статус привязки**\n\nВаш аккаунт авторизован в FatSecret"
            : "❌ **Статус привязки**\n\nВаш аккаунт не авторизован в FatSecret\n\n";

        $chat->edit($messageId)
            ->html($statusMessage)
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
        return 'checkFatSecretConnection';
    }
}