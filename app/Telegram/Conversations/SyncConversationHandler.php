<?php

namespace App\Telegram\Conversations;

use App\Telegram\Conversations\Contracts\ConversationContext;
use App\Telegram\Conversations\Contracts\ConversationHandler;
use App\Telegram\Conversations\Contracts\ConversationResult;
use App\Telegram\Exceptions\InvalidConversationStepException;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

/**
 * SyncConversationHandler - Handles FatSecret synchronization conversations
 *
 * Implements a simplified single-step conversation flow:
 * 1. input_date - User enters date for synchronization
 *    - After date validation, sync executes immediately (no second input needed)
 *    - Shows processing message during sync
 *    - Shows success/error message after completion
 *
 * Validates:
 * - Date format and validity
 *
 * Sync Type Context:
 * Expects 'sync_type' in conversation context as array with:
 * - 'name': sync type name (e.g., 'Полная синхронизация', 'Синхронизация веса')
 * - 'icon': emoji icon (e.g., '🔄', '⚖️', '🍽️')
 *
 * Usage:
 * This handler is registered with ConversationManager and automatically
 * invoked when conversation type is 'sync'.
 *
 * Note: Unlike other conversation handlers, this one performs the action
 * immediately after date validation without waiting for additional user input.
 */
class SyncConversationHandler implements ConversationHandler
{
    /**
     * Default sync type if not provided in context
     */
    private const DEFAULT_SYNC_TYPE = [
        'name' => 'Полная синхронизация',
        'icon' => '🔄'
    ];

    /**
     * @param DateValidationService $dateValidation Date parsing and validation
     * @param KeyboardFactory $keyboardFactory Keyboard creation
     * @param MessageResponseBuilder $messageBuilder Message formatting
     */
    public function __construct(
        private readonly DateValidationService $dateValidation,
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    /**
     * Handle a conversation step
     *
     * Routes the message to appropriate step handler based on current step.
     *
     * @param TelegraphChat $chat Telegram chat
     * @param string $step Current step (input_date or execute_sync)
     * @param Stringable $message User's message
     * @param ConversationContext $context Conversation context with state data
     * @return ConversationResult Result with status, message, and next step
     * @throws InvalidConversationStepException If step is not recognized
     */
    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        return match ($step) {
            'input_date' => $this->handleDateInput($chat, $message, $context),
            'execute_sync' => $this->handleSyncExecution($chat, $context),
            default => throw new InvalidConversationStepException($step),
        };
    }

    /**
     * Handle date input step
     *
     * Validates the date input and immediately executes synchronization.
     * Unlike other handlers, this doesn't wait for a second user input.
     *
     * Flow:
     * 1. Validate date
     * 2. Show processing message
     * 3. Execute sync
     * 4. Show success/error message
     * 5. Complete conversation
     *
     * @param TelegraphChat $chat Telegram chat
     * @param Stringable $message User's date input
     * @param ConversationContext $context Conversation context
     * @return ConversationResult Complete with success or error
     */
    private function handleDateInput(
        TelegraphChat $chat,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $dateResult = $this->dateValidation->validateAndParseDate((string) $message);

        if (!$dateResult['valid']) {
            return ConversationResult::error($dateResult['error']);
        }

        // Show processing message
        $chat->html("🔄 Выполняется синхронизация...")->send();

        // Execute sync immediately
        return $this->executeSynchronization(
            $context,
            $dateResult['formatted'],
            $dateResult['date']
        );
    }

    /**
     * Handle sync execution step (fallback)
     *
     * This step should not normally be reached as sync executes immediately
     * after date validation. It's kept for completeness and error handling.
     *
     * @param TelegraphChat $chat Telegram chat
     * @param ConversationContext $context Conversation context
     * @return ConversationResult Complete with message
     */
    private function handleSyncExecution(
        TelegraphChat $chat,
        ConversationContext $context
    ): ConversationResult {
        $date = $context->data['date'] ?? null;
        $dateObject = $context->data['date_object'] ?? null;

        if (!$date || !$dateObject) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Ошибка')
                    ->text('Не удалось получить дату. Попробуйте начать заново.')
                    ->build()
            );
        }

        return $this->executeSynchronization($context, $date, $dateObject);
    }

    /**
     * Execute the actual synchronization
     *
     * Performs the sync operation and returns appropriate result.
     * Handles errors gracefully.
     *
     * @param ConversationContext $context Conversation context
     * @param string $formattedDate Formatted date string
     * @param mixed $dateObject Date object
     * @return ConversationResult Complete with success or error message
     */
    private function executeSynchronization(
        ConversationContext $context,
        string $formattedDate,
        mixed $dateObject
    ): ConversationResult {
        $syncType = $context->data['sync_type'] ?? self::DEFAULT_SYNC_TYPE;

        try {
            // TODO: Phase 5 - Replace with actual sync service
            // $syncResult = $this->fatSecretSyncService->performSync(
            //     $context->user->id,
            //     $syncType['name'],
            //     $dateObject
            // );

            // Simulate sync process (temporary)
            sleep(1);

            // Show success message
            return ConversationResult::complete(
                message: $this->messageBuilder
                    ->create()
                    ->icon('✅')
                    ->title('Синхронизация завершена')
                    ->addField('Тип', $syncType['name'])
                    ->addField('Дата', $formattedDate)
                    ->text('Данные успешно синхронизированы с FatSecret')
                    ->build(),
                keyboard: $this->keyboardFactory->mainMenu()
            );

        } catch (\Exception $e) {
            // Show error message
            return ConversationResult::complete(
                message: $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Ошибка синхронизации')
                    ->text('Попробуйте позже или проверьте подключение к FatSecret')
                    ->addInstructions('Используйте /sync для повторной попытки')
                    ->build(),
                keyboard: $this->keyboardFactory->mainMenu()
            );
        }
    }

    /**
     * Get the conversation type identifier
     *
     * @return string 'sync'
     */
    public function getType(): string
    {
        return 'sync';
    }

    /**
     * Get the initial step for this conversation
     *
     * @return string 'input_date'
     */
    public function getInitialStep(): string
    {
        return 'input_date';
    }

    /**
     * Check if this handler can handle the given conversation type
     *
     * @param string $type Conversation type to check
     * @return bool True if type matches 'sync'
     */
    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}