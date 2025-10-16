<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * FatSecret Command Handler
 *
 * Handles the /fatsecret command which displays the FatSecret connection menu.
 *
 * This command allows users to:
 * - Connect to FatSecret via OAuth
 * - Check their FatSecret connection status
 * - Disconnect from FatSecret
 *
 * FatSecret integration enables automatic synchronization of nutrition data,
 * weight measurements, and food diary entries.
 */
class FatSecretCommandHandler implements TelegramCommandHandler
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
     * Handle the /fatsecret command
     *
     * Displays the FatSecret connection menu with options to:
     * - Check current connection status
     * - Initiate OAuth connection flow
     * - Disconnect from FatSecret
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    public function handle(TelegraphChat $chat): void
    {
        // Build FatSecret connection message
        $message = MessageResponseBuilder::create()
            ->icon('🔗')
            ->title('Привязка FatSecret')
            ->blank()
            ->text('Подключите ваш аккаунт FatSecret для автоматической синхронизации данных.')
            ->blank()
            ->addSection('Возможности интеграции', [
                '⚖️ **Синхронизация веса** - Автоматический импорт данных о весе',
                '🍎 **Дневник питания** - Импорт записей о приемах пищи',
                '🔄 **Двусторонняя синхронизация** - Данные обновляются в обе стороны',
            ])
            ->blank()
            ->addSection('Управление подключением', [
                '✅ **Проверить статус** - Узнать, подключен ли FatSecret',
                '🔗 **Подключить** - Авторизоваться через OAuth',
                '🚪 **Отключить** - Удалить авторизацию FatSecret',
            ])
            ->build();

        // Send message with FatSecret menu keyboard
        $chat->html($message)
            ->keyboard($this->keyboardFactory->fatSecretMenu())
            ->send();
    }

    /**
     * Get the command name
     *
     * @return string Command name without the leading slash
     */
    public function getCommandName(): string
    {
        return 'fatsecret';
    }
}