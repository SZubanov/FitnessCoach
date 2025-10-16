<?php

namespace App\Telegram\Exceptions;

/**
 * Invalid Conversation Step Exception
 *
 * Thrown when a conversation handler receives an unknown step name.
 * This typically indicates a bug in the conversation flow logic.
 */
class InvalidConversationStepException extends AbstractTelegramBotException
{
    /**
     * @var string
     */
    protected $message = 'Invalid conversation step';

    /**
     * Create exception for a specific step and type
     *
     * @param string $step The invalid step name
     * @param string|null $conversationType Optional conversation type
     * @return self
     */
    public static function forStep(string $step, ?string $conversationType = null): self
    {
        $message = "Invalid conversation step: '{$step}'";

        if ($conversationType) {
            $message .= " in conversation type: '{$conversationType}'";
        }

        return new self($message);
    }
}