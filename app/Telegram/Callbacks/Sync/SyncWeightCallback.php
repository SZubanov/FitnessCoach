<?php

namespace App\Telegram\Callbacks\Sync;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\DateValidationService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Sync Weight Callback Handler
 *
 * Initiates weight synchronization conversation with FatSecret.
 * Starts a conversation to select sync date for weight data only.
 * Callback name: 'syncWeight'
 */
class SyncWeightCallback implements CallbackHandler
{
    /**
     * Create callback handler instance
     *
     * @param ConversationStateService $conversationState Service to manage conversation state
     * @param DateValidationService $dateValidation Service to validate dates
     */
    public function __construct(
        private readonly ConversationStateService $conversationState,
        private readonly DateValidationService $dateValidation,
    ) {}

    /**
     * Handle the callback execution
     *
     * Starts a sync conversation for weight synchronization.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId The message ID to edit
     * @return void
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $this->startSyncConversation($chat, [
            'name' => 'Синхронизация веса',
            'icon' => '⚖️',
            'callback' => 'weight'
        ]);
    }

    /**
     * Start sync conversation with specific type
     *
     * Initializes conversation state and shows date input instructions.
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param array $syncType Sync type configuration
     * @return void
     */
    private function startSyncConversation(TelegraphChat $chat, array $syncType): void
    {
        $chatId = (string) $chat->chat_id;

        // Start conversation with sync type
        $this->conversationState->startConversation(
            $chatId,
            'sync',
            [
                'sync_type' => $syncType,
                'sync_callback' => $syncType['callback']
            ]
        );

        $this->conversationState->setStep($chatId, 'input_date');

        // Show date input instructions
        $instructions = $this->dateValidation->getDateInputInstructions();
        $chat->html("⚖️ **{$syncType['name']}**\n\n" . $instructions)->send();
    }

    /**
     * Get the callback action name
     *
     * @return string Callback name used in button actions
     */
    public function getCallbackName(): string
    {
        return 'syncWeight';
    }
}