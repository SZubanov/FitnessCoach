<?php

namespace App\Telegram\Callbacks\Sync;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Middleware\RequireFatSecretAuthMiddleware;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Show Sync Callback Handler
 *
 * Displays the synchronization menu with FatSecret sync options.
 * Requires both account linking and FatSecret authorization.
 * Callback name: 'showSync'
 */
class ShowSyncCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     * @param TelegramUserService $userService Service to manage Telegram users
     */
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly TelegramUserService $userService,
    ) {}

    /**
     * Handle the callback execution
     *
     * Checks account linking and FatSecret auth via middleware,
     * then shows sync menu.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        // Build middleware pipeline for authorization checks
        $pipeline = new MiddlewarePipeline([
            new RequireAccountLinkMiddleware($this->userService, $this->keyboardFactory),
            new RequireFatSecretAuthMiddleware($this->keyboardFactory),
        ]);

        // Execute through pipeline
        $pipeline->through($chat, function ($chat) use ($messageId) {
            $this->showSyncMenu($chat, $messageId);
        });
    }

    /**
     * Show synchronization menu
     *
     * Displays menu with sync type options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    private function showSyncMenu(TelegraphChat $chat, ?int $messageId): void
    {
        $instructionsText = "🔄 **Синхронизация с FatSecret**\n\n" .
            "Выберите тип синхронизации:\n\n" .
            "💡 **Доступные опции:**\n" .
            "🔄 **Полная** - Синхронизация всех данных\n" .
            "⚖️ **Вес** - Только данные о весе\n" .
            "🍎 **Дневник питания** - Только питание\n\n" .
            "⚠️ **Требуется подключение к FatSecret**";

        $chat->edit($messageId)
            ->html($instructionsText)
            ->keyboard($this->keyboardFactory->syncMenu())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'showSync';
    }
}