<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Show Weight Callback Handler
 *
 * Displays the weight tracking menu.
 * Requires account linking middleware.
 * Callback name: 'showWeight'
 */
class ShowWeightCallback implements CallbackHandler
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
     * Checks account linking via middleware, then shows weight menu.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        // Build middleware pipeline for authorization check
        $pipeline = new MiddlewarePipeline([
            new RequireAccountLinkMiddleware($this->userService, $this->keyboardFactory),
        ]);

        // Execute through pipeline
        $pipeline->through($chat, function ($chat) use ($messageId) {
            $this->showWeightMenu($chat, $messageId);
        });
    }

    /**
     * Show weight menu
     *
     * Displays menu with weight tracking options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    private function showWeightMenu(TelegraphChat $chat, ?int $messageId): void
    {
        $message = "⚖️ **Отслеживание веса**\n\n" .
            "Ведите учет вашего веса:\n\n" .
            "➕ **Добавить вес** - Новая запись\n" .
            "📊 **Текущий вес** - Последние показания\n" .
            "📈 **Динамика** - График изменений\n" .
            "🔄 **Синхронизация** - Импорт из FatSecret\n\n" .
            "💡 **Совет:** Взвешивайтесь утром натощак для точности";

        $chat->edit($messageId)
            ->markdown($message)
            ->keyboard($this->keyboardFactory->weight())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'showWeight';
    }
}
