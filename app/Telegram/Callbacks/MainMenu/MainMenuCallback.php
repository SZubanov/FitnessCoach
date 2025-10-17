<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Main Menu Callback Handler
 *
 * Returns user to the main menu.
 * Used by "Back to Main Menu" buttons throughout the bot.
 * Callback name: 'mainMenu'
 */
class MainMenuCallback implements CallbackHandler
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
     * Edits the message to show the main menu with primary navigation.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $chat->edit($messageId)
            ->html('🏠 Главное меню FitnessCoach')
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'mainMenu';
    }
}