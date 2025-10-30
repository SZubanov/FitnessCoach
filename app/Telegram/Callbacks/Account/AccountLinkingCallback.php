<?php

namespace App\Telegram\Callbacks\Account;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Account Linking Callback Handler
 *
 * Displays the account linking menu with options to generate code,
 * check status, or unlink account.
 * Callback name: 'accountLinking'
 */
class AccountLinkingCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the callback execution
     *
     * Edits the message to show account linking menu.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $chat->edit($messageId)
            ->markdown('🔗 Привязка аккаунта')
            ->keyboard($this->keyboardFactory->accountMenu())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'accountLinking';
    }
}
