<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Help Command Handler
 *
 * Handles the /help command which displays available commands
 * and their descriptions to the user.
 *
 * Provides comprehensive help information including:
 * - Basic commands (start, help)
 * - Quick action commands (sync)
 * - Management commands (account, fatsecret)
 */
class HelpCommandHandler implements TelegramCommandHandler
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
     * Handle the /help command
     *
     * Sends a comprehensive help message with all available commands
     * and displays a keyboard with navigation options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    public function handle(TelegraphChat $chat): void
    {
        // Build help message using MessageResponseBuilder
        $helpMessage = MessageResponseBuilder::create()
            ->icon('🆘')
            ->title('Помощь по командам FitnessCoach')
            ->blank()
            ->text($this->formatCommandsList())
            ->blank()
            ->text('🔗 **Команды с параметрами:**')
            ->text('• /sync полная - Полная синхронизация с FatSecret')
            ->build();

        // Send help message with keyboard
        $chat->markdown($helpMessage)
            ->keyboard($this->keyboardFactory->help())
            ->send();
    }

    /**
     * Format the commands list for the help message
     *
     * Organizes commands into logical categories:
     * - Basic commands
     * - Quick commands
     * - Management commands
     *
     * @return string Formatted commands list
     */
    private function formatCommandsList(): string
    {
        $sections = [];

        // Basic commands section
        $sections[] = '📋 **Основные команды:**';
        $sections[] = '/start - Главное меню и приветствие';
        $sections[] = '/help - Помощь и список команд';

        // Quick commands section
        $sections[] = '';
        $sections[] = '🚀 **Быстрые команды:**';
        $sections[] = '/sync [тип] - Синхронизация с FatSecret';

        // Management commands section
        $sections[] = '';
        $sections[] = '⚙️ **Управление:**';
        $sections[] = '/account - Привязка аккаунта';
        $sections[] = '/fatsecret - Подключение к FatSecret';

        return implode("\n", $sections);
    }

    /**
     * Get the command name
     *
     * @return string Command name without the leading slash
     */
    public function getCommandName(): string
    {
        return 'help';
    }
}
