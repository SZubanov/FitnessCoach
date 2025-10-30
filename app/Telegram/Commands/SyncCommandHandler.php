<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Middleware\RequireFatSecretAuthMiddleware;
use App\Telegram\Services\MessageResponseBuilder;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Sync Command Handler
 *
 * Handles the /sync command which displays the FatSecret synchronization menu.
 *
 * This command is protected by middleware and requires:
 * 1. Account linking (Telegram → FitnessCoach)
 * 2. FatSecret authorization (OAuth)
 *
 * Available synchronization types:
 * - Full sync (all data)
 * - Weight only
 * - Food diary only
 */
class SyncCommandHandler implements TelegramCommandHandler
{
    /**
     * Create command handler instance
     *
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     * @param TelegramUserService $userService Service to manage Telegram users
     */
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly TelegramUserService $userService,
    ) {}

    /**
     * Handle the /sync command
     *
     * Executes through middleware pipeline to ensure:
     * - User has linked Telegram account to FitnessCoach
     * - User has authorized FatSecret integration
     *
     * If checks pass, displays synchronization menu with options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    public function handle(TelegraphChat $chat): void
    {
        // Build middleware pipeline for authorization checks
        $pipeline = new MiddlewarePipeline([
            new RequireAccountLinkMiddleware($this->userService, $this->keyboardFactory),
            new RequireFatSecretAuthMiddleware($this->keyboardFactory),
        ]);

        // Execute through pipeline
        $pipeline->through($chat, function ($chat) {
            // Only reached if both middleware checks pass
            // User is available at $chat->_authenticatedUser if needed
            $this->showSyncMenu($chat);
        });
    }

    /**
     * Show synchronization menu
     *
     * Displays menu with sync type options and requirements information.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @return void
     */
    private function showSyncMenu(TelegraphChat $chat): void
    {
        // Build sync menu message
        $message = MessageResponseBuilder::create()
            ->icon('🔄')
            ->title('Синхронизация с FatSecret')
            ->blank()
            ->text('Выберите тип синхронизации:')
            ->blank()
            ->addSection('💡 Доступные опции', [
                '🔄 **Полная** - Синхронизация всех данных',
                '⚖️ **Вес** - Только данные о весе',
                '🍎 **Дневник питания** - Только питание',
            ])
            ->blank()
            ->warning('⚠️ Требуется подключение к FatSecret')
            ->build();

        // Send message with sync menu keyboard
        $chat->markdown($message)
            ->keyboard($this->keyboardFactory->syncMenu())
            ->send();
    }

    /**
     * Get the command name
     *
     * @return string Command name without the leading slash
     */
    public function getCommandName(): string
    {
        return 'sync';
    }
}
