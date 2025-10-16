<?php

namespace App\Telegram\Exceptions;

/**
 * No Handler For Conversation Type Exception
 *
 * Thrown when the ConversationManager cannot find a handler
 * for the requested conversation type.
 */
class NoHandlerForConversationTypeException extends AbstractTelegramBotException
{
    /**
     * @var string
     */
    protected $message = 'No handler found for conversation type';

    /**
     * Create exception for a specific conversation type
     *
     * @param string $type The conversation type with no handler
     * @return self
     */
    public static function forType(string $type): self
    {
        return new self("No handler found for conversation type: '{$type}'");
    }

    /**
     * Create exception with available types
     *
     * @param string $requestedType The requested type
     * @param array<string> $availableTypes Available conversation types
     * @return self
     */
    public static function withAvailableTypes(string $requestedType, array $availableTypes): self
    {
        $available = implode(', ', $availableTypes);

        return new self(
            "No handler found for conversation type: '{$requestedType}'. " .
            "Available types: {$available}"
        );
    }
}