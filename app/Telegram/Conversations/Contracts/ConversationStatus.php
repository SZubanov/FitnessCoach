<?php

namespace App\Telegram\Conversations\Contracts;

/**
 * Conversation status enum
 *
 * Represents the current state of a conversation step
 */
enum ConversationStatus
{
    /**
     * Continue to next step in the conversation
     */
    case Continue;

    /**
     * Conversation finished successfully
     */
    case Complete;

    /**
     * Error occurred, stay in current step
     */
    case Error;
}