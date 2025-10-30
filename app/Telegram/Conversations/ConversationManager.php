<?php

namespace App\Telegram\Conversations;

use App\Models\User;
use App\Telegram\Conversations\Contracts\ConversationContext;
use App\Telegram\Conversations\Contracts\ConversationHandler;
use App\Telegram\Conversations\Contracts\ConversationResult;
use App\Telegram\Conversations\Contracts\ConversationStatus;
use App\Telegram\Exceptions\NoActiveConversationException;
use App\Telegram\Exceptions\NoHandlerForConversationTypeException;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

/**
 * ConversationManager - Manages conversation flow routing
 *
 * Implements the Strategy pattern to delegate conversation handling to
 * specialized handlers based on conversation type.
 *
 * Responsibilities:
 * - Route incoming messages to appropriate conversation handlers
 * - Manage conversation lifecycle (start, continue, complete)
 * - Coordinate state persistence through ConversationStateService
 * - Process conversation results and update state accordingly
 *
 * Usage:
 * ```php
 * // Start a new conversation
 * $manager->start($chatId, 'weight', ['selected_date' => '2024-10-27']);
 *
 * // Route incoming message to active conversation
 * $manager->route($chat, $message);
 * ```
 */
class ConversationManager
{
    /**
     * @param ConversationStateService $stateService State management service
     * @param TelegramUserService $userService User resolution service
     * @param iterable<ConversationHandler> $handlers Registered conversation handlers
     */
    public function __construct(
        private readonly ConversationStateService $stateService,
        private readonly TelegramUserService $userService,
        private readonly iterable $handlers,
    ) {}

    /**
     * Route incoming message to appropriate conversation handler
     *
     * This is the main entry point for processing conversation messages.
     * It retrieves the conversation state, finds the appropriate handler,
     * executes it, and processes the result.
     *
     * @param TelegraphChat $chat Telegram chat
     * @param Stringable $message User's message
     * @return void
     * @throws NoActiveConversationException If no active conversation exists
     * @throws NoHandlerForConversationTypeException If no handler found for type
     */
    public function route(TelegraphChat $chat, Stringable $message): void
    {
        $chatId = (string) $chat->chat_id;

        if (!$this->stateService->isInConversation($chatId)) {
            throw new NoActiveConversationException();
        }

        $type = $this->stateService->getConversationType($chatId);
        $step = $this->stateService->getStep($chatId);
        $data = $this->stateService->getAllData($chatId);

        $handler = $this->findHandler($type);
        $user = $this->getUser($chat);
        $context = new ConversationContext($chatId, $user, $data);

        $result = $handler->handle($chat, $step, $message, $context);

        $this->processResult($chat, $chatId, $result);
    }

    /**
     * Start a new conversation
     *
     * Initializes conversation state and sets the initial step based on
     * the handler's configuration.
     *
     * @param string $chatId Telegram chat ID
     * @param string $type Conversation type (e.g., 'measurement', 'weight')
     * @param array $initialData Optional initial data to store in state
     * @return void
     * @throws NoHandlerForConversationTypeException If no handler found for type
     */
    public function start(
        string $chatId,
        string $type,
        array $initialData = []
    ): void {
        $handler = $this->findHandler($type);

        $this->stateService->startConversation(
            $chatId,
            $type,
            $initialData
        );

        $this->stateService->setStep($chatId, $handler->getInitialStep());
    }

    /**
     * Find handler for conversation type
     *
     * Iterates through registered handlers to find one that can handle
     * the specified conversation type.
     *
     * @param string $type Conversation type to find handler for
     * @return ConversationHandler The handler instance
     * @throws NoHandlerForConversationTypeException If no handler found
     */
    private function findHandler(string $type): ConversationHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($type)) {
                return $handler;
            }
        }

        throw new NoHandlerForConversationTypeException("No handler for type: {$type}");
    }

    /**
     * Get user from chat
     *
     * Resolves the authenticated user associated with the chat.
     *
     * @param TelegraphChat $chat Telegram chat
     * @return User The authenticated user
     */
    private function getUser(TelegraphChat $chat): User
    {
        return $this->userService->getCurrentUser($chat->chat_id);
    }

    /**
     * Process conversation result
     *
     * Handles the result returned by a conversation handler:
     * - Sends message to user (if provided)
     * - Attaches keyboard (if provided)
     * - Updates conversation state based on status
     *
     * @param TelegraphChat $chat Telegram chat
     * @param string $chatId Chat ID
     * @param ConversationResult $result Handler result to process
     * @return void
     */
    private function processResult(
        TelegraphChat $chat,
        string $chatId,
        ConversationResult $result
    ): void {
        // Send message if provided
        if ($result->message) {
            $messageBuilder = $chat->markdown($result->message);

            if ($result->keyboard) {
                $messageBuilder->keyboard($result->keyboard);
            }

            $messageBuilder->send();
        }

        // Update state based on status
        match ($result->status) {
            ConversationStatus::Continue => $this->continueConversation($chatId, $result),
            ConversationStatus::Complete => $this->completeConversation($chatId),
            ConversationStatus::Error => $this->handleError($chatId, $result),
        };
    }

    /**
     * Continue conversation to next step
     *
     * Updates the conversation step (if specified) and stores any
     * additional data returned by the handler.
     *
     * @param string $chatId Chat ID
     * @param ConversationResult $result Handler result
     * @return void
     */
    private function continueConversation(string $chatId, ConversationResult $result): void
    {
        if ($result->nextStep) {
            $this->stateService->setStep($chatId, $result->nextStep);
        }

        // Store any additional data
        foreach ($result->data as $key => $value) {
            $this->stateService->setData($chatId, $key, $value);
        }
    }

    /**
     * Complete conversation and cleanup
     *
     * Ends the conversation and removes all state from cache.
     *
     * @param string $chatId Chat ID
     * @return void
     */
    private function completeConversation(string $chatId): void
    {
        $this->stateService->endConversation($chatId);
    }

    /**
     * Handle conversation error
     *
     * Currently just a placeholder for error handling logic.
     * The error message has already been sent to the user by processResult().
     * The conversation stays in the current step, allowing the user to retry.
     *
     * @param string $chatId Chat ID
     * @param ConversationResult $result Handler result with error details
     * @return void
     */
    private function handleError(string $chatId, ConversationResult $result): void
    {
        // Stay in current step, error message already sent
        // Could log error here if needed
    }
}
