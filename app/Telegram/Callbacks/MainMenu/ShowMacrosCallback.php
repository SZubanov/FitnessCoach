<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Show Macros (КБЖУ) Callback Handler
 *
 * Displays the macronutrient tracking menu.
 * Requires account linking middleware.
 * Callback name: 'showMacros'
 */
class ShowMacrosCallback implements CallbackHandler
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
     * Checks account linking via middleware, then shows macros menu.
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
            $this->showMacrosMenu($chat, $messageId);
        });
    }

    /**
     * Show macros menu
     *
     * Displays menu with macronutrient tracking options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    private function showMacrosMenu(TelegraphChat $chat, ?int $messageId): void
    {
        $message = "🍎 **КБЖУ - Макронутриенты**\n\n" .
            "Отслеживание калорий и макронутриентов:\n\n" .
            "➕ **Добавить прием пищи** - Записать еду\n" .
            "📊 **Сегодня** - Статистика за сегодня\n" .
            "📅 **История** - Просмотр по дням\n" .
            "🔄 **Синхронизация** - Импорт из FatSecret\n\n" .
            "💡 **К** - Калории, **Б** - Белки, **Ж** - Жиры, **У** - Углеводы";

        $chat->edit($messageId)
            ->html($message)
            ->keyboard($this->keyboardFactory->macros())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'showMacros';
    }
}