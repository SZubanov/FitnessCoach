<?php

namespace App\Telegram\Conversations\Contracts;

use DefStudio\Telegraph\Keyboard\Keyboard;

/**
 * Conversation result DTO
 *
 * Represents the outcome of processing a conversation step
 */
class ConversationResult
{
    /**
     * @param ConversationStatus $status Result status
     * @param string|null $nextStep Next step to transition to (if Continue)
     * @param string|null $message Message to send to user
     * @param Keyboard|null $keyboard Keyboard to attach to message
     * @param array $data Additional data to store in conversation state
     */
    public function __construct(
        public readonly ConversationStatus $status,
        public readonly ?string $nextStep = null,
        public readonly ?string $message = null,
        public readonly ?Keyboard $keyboard = null,
        public readonly array $data = [],
    ) {}

    /**
     * Create a Continue result
     *
     * @param string $nextStep Next step name
     * @param string $message Message to send
     * @param array $data Additional data to store
     * @return self
     */
    public static function continue(
        string $nextStep,
        string $message,
        array $data = []
    ): self {
        return new self(
            status: ConversationStatus::Continue,
            nextStep: $nextStep,
            message: $message,
            data: $data
        );
    }

    /**
     * Create a Complete result
     *
     * @param string $message Success message to send
     * @param Keyboard|null $keyboard Optional keyboard (e.g., main menu)
     * @return self
     */
    public static function complete(
        string $message,
        ?Keyboard $keyboard = null
    ): self {
        return new self(
            status: ConversationStatus::Complete,
            message: $message,
            keyboard: $keyboard
        );
    }

    /**
     * Create an Error result
     *
     * Stays in the same step and shows error message
     *
     * @param string $message Error message to send
     * @return self
     */
    public static function error(string $message): self
    {
        return new self(
            status: ConversationStatus::Continue, // Stay in same step
            message: $message
        );
    }
}