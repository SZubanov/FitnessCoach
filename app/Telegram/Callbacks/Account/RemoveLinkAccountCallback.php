<?php

namespace App\Telegram\Callbacks\Account;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramAccountService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Remove Link Account Callback Handler
 *
 * Unlinks the Telegram account from FitnessCoach user.
 * After unlinking, user will need to generate a new code to re-link.
 * Callback name: 'removeLinkAccount'
 */
class RemoveLinkAccountCallback implements CallbackHandler
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
     * Removes the account link and displays confirmation message.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $this->accountService->removeLinkAccount($chat->chat_id);

        $message = "❌ **Отвязка аккаунта**\n\n" .
            "Ваш аккаунт был отвязан от системы FitnessCoach\n\n" .
            "Для повторной привязки используйте функцию \"Получить код для привязки\"";

        $chat->edit($messageId)
            ->html($message)
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
        return 'removeLinkAccount';
    }
}