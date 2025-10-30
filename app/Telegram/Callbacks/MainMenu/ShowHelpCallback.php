<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Show Help Callback Handler
 *
 * Displays the help menu with feature descriptions and date format info.
 * Callback name: 'showHelp'
 */
class ShowHelpCallback implements CallbackHandler
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
     * Edits the message to show help information with feature descriptions.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $message = "📖 **Помощь FitnessCoach Bot**\n\n" .
            "**Доступные функции:**\n" .
            "📏 **Замеры** - Записывайте измерения тела\n" .
            "🔄 **Синхронизация** - Синхронизация с FatSecret\n" .
            "🍎 **КБЖУ** - Отслеживание калорий и макронутриентов\n" .
            "⚖️ **Вес** - Записывайте показания веса\n" .
            "⚙️ **Настройки** - Управление аккаунтом и подключениями\n\n" .
            "**Формат даты:** DD.MM.YYYY или DD/MM/YYYY\n" .
            "**Пример:** 25.12.2024 или 25/12/2024";

        $chat->edit($messageId)
            ->markdown($message)
            ->keyboard($this->keyboardFactory->help())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'showHelp';
    }
}
