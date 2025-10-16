<?php

namespace App\Telegram\Conversations\Contracts;

use App\Models\User;

/**
 * Conversation context DTO
 *
 * Contains all contextual information needed for a conversation step
 */
class ConversationContext
{
    /**
     * @param string $chatId Telegram chat ID
     * @param User $user Authenticated user
     * @param array $data Conversation state data
     */
    public function __construct(
        public readonly string $chatId,
        public readonly User $user,
        public readonly array $data = [],
    ) {}
}