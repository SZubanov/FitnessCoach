<?php

namespace App\Telegram\Callbacks\FatSecret;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * FatSecret Connect Callback Handler
 *
 * Displays the FatSecret connection menu with options to connect,
 * check status, or logout from FatSecret.
 * Callback name: 'fatSecretConnect'
 */
class FatSecretConnectCallback implements CallbackHandler
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
     * Edits the message to show FatSecret connection menu.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $chat->edit($messageId)
            ->html('🔗 Привязка FatSecret')
            ->keyboard($this->keyboardFactory->fatSecretMenu())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'fatSecretConnect';
    }
}