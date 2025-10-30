<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Show Settings Callback Handler
 *
 * Displays the settings menu with account linking and FatSecret options.
 * Callback name: 'showSettings'
 */
class ShowSettingsCallback implements CallbackHandler
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
     * Edits the message to show settings menu with available options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $message = "⚙️ **Настройки**\n\n" .
            "Управление вашим аккаунтом и подключениями:\n\n" .
            "🔗 **Привязка аккаунта** - Управление связью Telegram с FitnessCoach\n" .
            "🔐 **FatSecret** - Подключение к FatSecret API\n" .
            "👤 **Профиль** - Ваши личные данные\n\n" .
            "Выберите раздел для управления:";

        $chat->edit($messageId)
            ->markdown($message)
            ->keyboard($this->keyboardFactory->settings())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'showSettings';
    }
}
