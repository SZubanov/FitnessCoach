<?php

namespace App\Telegram\Conversations\Contracts;

use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

/**
 * Interface for conversation handlers (Strategy pattern)
 *
 * Each conversation type (measurement, weight, macro, sync) should
 * implement this interface to be registered with ConversationManager.
 */
interface ConversationHandler
{
    /**
     * Handle a conversation step
     *
     * @param TelegraphChat $chat Telegram chat
     * @param string $step Current step name
     * @param Stringable $message User's message
     * @param ConversationContext $context Conversation context
     * @return ConversationResult Result of processing the step
     */
    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult;

    /**
     * Get the conversation type identifier
     *
     * @return string Type (e.g., 'measurement', 'weight', 'macro', 'sync')
     */
    public function getType(): string;

    /**
     * Get the initial step for this conversation
     *
     * @return string Step name (e.g., 'input_date')
     */
    public function getInitialStep(): string;

    /**
     * Check if this handler can handle the given conversation type
     *
     * @param string $type Conversation type to check
     * @return bool True if this handler can handle the type
     */
    public function canHandle(string $type): bool;
}