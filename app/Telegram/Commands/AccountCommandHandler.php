<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Account Command Handler
 *
 * Handles the /account command which displays the account linking menu.
 *
 * This command allows users to:
 * - Generate a link code for account binding
 * - Check their current account linking status
 * - Remove account link if already linked
 *
 * Account linking connects a Telegram account with a FitnessCoach user account,
 * enabling access to personalized features and data.
 */
class AccountCommandHandler implements TelegramCommandHandler
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
     * Handle the /account command
     *
     * Displays the account linking menu with options to:
     * - Generate a temporary link code
     * - Check account linking status
     * - Remove existing account link
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    public function handle(TelegraphChat $chat): void
    {
        // Build account linking message
        $message = MessageResponseBuilder::create()
            ->icon('🔗')
            ->title('Привязка аккаунта')
            ->blank()
            ->text('Управление связью вашего Telegram аккаунта с FitnessCoach.')
            ->blank()
            ->addSection('Доступные действия', [
                '📝 **Получить код** - Создать временный код для привязки (15 минут)',
                '✅ **Проверить статус** - Узнать, привязан ли ваш аккаунт',
                '❌ **Отвязать** - Удалить связь с аккаунтом FitnessCoach',
            ])
            ->build();

        // Send message with account menu keyboard
        $chat->markdown($message)
            ->keyboard($this->keyboardFactory->accountMenu())
            ->send();
    }

    /**
     * Get the command name
     *
     * @return string Command name without the leading slash
     */
    public function getCommandName(): string
    {
        return 'account';
    }
}
