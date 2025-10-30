<?php

namespace App\Telegram\Callbacks\Macros;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Select Macro Carbs Callback Handler
 *
 * Initiates carbohydrates tracking conversation.
 * Requires account linking middleware.
 * Starts conversation to collect date and carbs value.
 * Callback name: 'selectMacroCarbs'
 */
class SelectMacroCarbsCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param ConversationStateService $conversationState Service to manage conversation state
     * @param DateValidationService $dateValidation Service to validate dates
     * @param TelegramUserService $userService Service to manage Telegram users
     * @param KeyboardFactory $keyboardFactory Factory to build keyboards
     */
    public function __construct(
        private readonly ConversationStateService $conversationState,
        private readonly DateValidationService $dateValidation,
        private readonly TelegramUserService $userService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the callback execution
     *
     * Checks account linking via middleware, then starts macro conversation.
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
        $pipeline->through($chat, function ($chat) {
            $this->startMacroConversation($chat, [
                'name' => 'углеводы',
                'unit' => 'г',
                'icon' => '🍞'
            ]);
        });
    }

    /**
     * Start macro conversation with specific type
     *
     * Initializes conversation state and shows date input instructions.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param array $macroType Macro type configuration
     * @return void
     */
    private function startMacroConversation(TelegraphChat $chat, array $macroType): void
    {
        $chatId = (string) $chat->chat_id;

        // Start conversation with macro type
        $this->conversationState->startConversation(
            $chatId,
            'macro',
            ['macro_type' => $macroType]
        );

        $this->conversationState->setStep($chatId, 'input_date');

        // Show date input instructions
        $instructions = $this->dateValidation->getDateInputInstructions();
        $chat->markdown($instructions)->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'selectMacroCarbs';
    }
}
