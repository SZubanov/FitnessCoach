<?php

namespace App\Telegram\Callbacks\Account;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramAccountService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Check Link Status Callback Handler
 *
 * Checks and displays the account linking status between Telegram and FitnessCoach.
 * Shows whether the current Telegram account is linked to a FitnessCoach user.
 * Callback name: 'checkLinkStatus'
 */
class CheckLinkStatusCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param TelegramAccountService $accountService Service to manage account linking
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly TelegramAccountService $accountService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the callback execution
     *
     * Checks linking status and displays appropriate message.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $isLinked = $this->accountService->checkLinkAccountStatus($chat->chat_id);

        $statusMessage = $isLinked
            ? "✅ **Статус привязки**\n\nВаш аккаунт привязан к системе FitnessCoach"
            : "❌ **Статус привязки**\n\nВаш аккаунт не привязан к системе FitnessCoach\n\n" .
              "Используйте кнопку \"Получить код для привязки\" для создания кода";

        $chat->edit($messageId)
            ->markdown($statusMessage)
            ->keyboard($this->keyboardFactory->accountBack())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'checkLinkStatus';
    }
}
