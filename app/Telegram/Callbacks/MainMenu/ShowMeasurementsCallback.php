<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Show Measurements Callback Handler
 *
 * Displays the measurements menu for body tracking.
 * Requires account linking middleware.
 * Callback name: 'showMeasurements'
 */
class ShowMeasurementsCallback implements CallbackHandler
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
     * Checks account linking via middleware, then shows measurements menu.
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
            $this->showMeasurementsMenu($chat, $messageId);
        });
    }

    /**
     * Show measurements menu
     *
     * Displays menu with measurement tracking options.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    private function showMeasurementsMenu(TelegraphChat $chat, ?int $messageId): void
    {
        $message = "📏 **Замеры тела**\n\n" .
            "Отслеживайте изменения ваших измерений:\n\n" .
            "📐 **Новый замер** - Добавить новое измерение\n" .
            "📊 **История** - Просмотр истории замеров\n" .
            "📈 **Прогресс** - График изменений\n\n" .
            "💡 **Совет:** Делайте замеры в одно и то же время для точности";

        $chat->edit($messageId)
            ->markdown($message)
            ->keyboard($this->keyboardFactory->measurements())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'showMeasurements';
    }
}
