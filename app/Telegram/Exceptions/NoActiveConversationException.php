<?php

namespace App\Telegram\Exceptions;

/**
 * No Active Conversation Exception
 *
 * Thrown when attempting to route a message but no conversation is active
 * for the given chat.
 */
class NoActiveConversationException extends AbstractTelegramBotException
{
    /**
     * @var string
     */
    protected $message = 'No active conversation found';

    /**
     * Create exception for a specific chat
     *
     * @param string $chatId The chat ID with no active conversation
     * @return self
     */
    public static function forChat(string $chatId): self
    {
        return new self("No active conversation found for chat: {$chatId}");
    }
}