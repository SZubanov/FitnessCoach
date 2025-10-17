<?php

namespace App\Telegram\Callbacks\Account;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramAccountService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Generate Link Code Callback Handler
 *
 * Generates a temporary code for linking Telegram account to FitnessCoach.
 * The code is valid for 15 minutes and can be used in the web interface.
 * Callback name: 'generateLinkCode'
 */
class GenerateLinkCodeCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param TelegramAccountService $accountService Service to manage account linking
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly TelegramAccountService $accountService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the callback execution
     *
     * Generates a link code and displays it to the user with instructions.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $linkCode = $this->accountService->generateLinkAccountCode($chat->chat_id);

        $message = "🔗 **Код для привязки аккаунта**\n\n" .
            "Ваш код: `{$linkCode}`\n\n" .
            "⏰ Код действителен 15 минут\n" .
            "🌐 Используйте этот код в веб-интерфейсе для привязки аккаунта\n\n" .
            "⚠️ **Внимание:** При создании нового кода, старый перестает действовать";

        $chat->edit($messageId)
            ->html($message)
            ->keyboard($this->keyboardFactory->accountBack())
            ->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'generateLinkCode';
    }
}