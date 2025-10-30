<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Start Command Handler
 *
 * Handles the /start command which shows the welcome message
 * and main menu to the user.
 *
 * This is typically the first command users interact with when
 * starting the bot.
 */
class StartCommandHandler implements TelegramCommandHandler
{
    /**
     * Create command handler instance
     *
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the /start command
     *
     * Sends a welcome message with bot features and displays
     * the main menu keyboard for navigation.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    public function handle(TelegraphChat $chat): void
    {
        // Build welcome message using MessageResponseBuilder
        $welcomeMessage = MessageResponseBuilder::create()
            ->greeting('FitnessCoach')
            ->blank()
            ->text('Я помогу вам отслеживать:')
            ->bulletList([
                '⚖️ Вес и измерения тела',
                '🍎 Макронутриенты (КБЖУ)',
                '🔄 Синхронизацию с FatSecret',
            ])
            ->blank()
            ->text('Используйте меню ниже для начала работы:')
            ->build();

        // Send welcome message
        $chat->markdown($welcomeMessage)->send();

        // Send main menu with keyboard
        $this->sendMainMenu($chat);
    }

    /**
     * Send main menu message with keyboard
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    private function sendMainMenu(TelegraphChat $chat): void
    {
        $chat->markdown('🏠 Главное меню FitnessCoach')
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }

    /**
     * Get the command name
     *
     * @return string Command name without the leading slash
     */
    public function getCommandName(): string
    {
        return 'start';
    }
}
