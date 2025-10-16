<?php

namespace App\Telegram\Callbacks\Contracts;

use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Interface for callback query handlers
 *
 * Each callback handler should implement this interface to be
 * registered with the CallbackRegistry.
 */
interface CallbackHandler
{
    /**
     * Handle the callback execution
     *
     * @param TelegraphChat $chat The chat where callback was triggered
     * @param int|null $messageId The message ID to edit (for inline keyboards)
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void;

    /**
     * Get the callback action name
     *
     * This should match the action name used in Button::make()->action('name')
     *
     * @return string Callback action name
     */
    public function getCallbackName(): string;
}