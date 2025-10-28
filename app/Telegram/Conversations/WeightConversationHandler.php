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
 * WeightConversationHandler - Handles weight tracking conversations
 *
 * Implements a two-step conversation flow:
 * 1. input_date - User enters weight measurement date
 * 2. input_value - User enters weight value in kilograms
 *
 * Validates:
 * - Date format and validity
 * - Numeric value input (supports both comma and dot as decimal separator)
 * - Value range (20-300 kg)
 *
 * Usage:
 * This handler is registered with ConversationManager and automatically
 * invoked when conversation type is 'weight'.
 */
class WeightConversationHandler implements ConversationHandler
{
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
     * @param string $step Current step (input_date or input_value)
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
            'input_date' => $this->handleDateInput($message, $context),
            'input_value' => $this->handleValueInput($message, $context),
            default => throw new InvalidConversationStepException($step),
        };
    }

    /**
     * Handle date input step
     *
     * Validates the date input and transitions to value input step.
     *
     * @param Stringable $message User's date input
     * @param ConversationContext $context Conversation context
     * @return ConversationResult Continue to input_value or error
     */
    private function handleDateInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $dateResult = $this->dateValidation->validateAndParseDate((string) $message);

        if (!$dateResult['valid']) {
            return ConversationResult::error($dateResult['error']);
        }

        return ConversationResult::continue(
            nextStep: 'input_value',
            message: $this->messageBuilder
                ->create()
                ->icon('⚖️')
                ->title('Запись веса')
                ->text('Введите ваш вес в килограммах:')
                ->addExample('Например: 70.5 или 85,2')
                ->build(),
            data: [
                'date' => $dateResult['formatted'],
                'date_object' => $dateResult['date'],
            ]
        );
    }

    /**
     * Handle value input step
     *
     * Validates the weight value, saves to database, and completes conversation.
     * Supports both comma and dot as decimal separator.
     *
     * @param Stringable $message User's value input
     * @param ConversationContext $context Conversation context
     * @return ConversationResult Complete with success or error
     */
    private function handleValueInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $valueInput = trim((string) $message);

        // Replace comma with dot for decimal separator (Russian locale support)
        $valueInput = str_replace(',', '.', $valueInput);

        // Validate numeric input
        if (!is_numeric($valueInput)) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Неверное значение')
                    ->text('Введите число (вес в килограммах):')
                    ->addExample('Например: 70.5 или 85,2')
                    ->build()
            );
        }

        $value = (float) $valueInput;

        // Validate range (20-300 kg)
        if ($value < 20 || $value > 300) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Значение вне допустимого диапазона')
                    ->text('Введите вес от 20 до 300 кг:')
                    ->build()
            );
        }

        $date = $context->data['date'];

        // TODO: Phase 5 - Replace with SaveWeight action
        // $weight = $this->saveWeightAction->handle([
        //     'user_id' => $context->user->id,
        //     'value' => $value,
        //     'date' => $date,
        // ]);

        // Temporary: Just format success message with data we have
        return ConversationResult::complete(
            message: $this->messageBuilder
                ->create()
                ->icon('✅')
                ->title('Вес сохранен')
                ->addField('Значение', "{$value} кг")
                ->addField('Дата', $date)
                ->build(),
            keyboard: $this->keyboardFactory->mainMenu()
        );
    }

    /**
     * Get the conversation type identifier
     *
     * @return string 'weight'
     */
    public function getType(): string
    {
        return 'weight';
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
     * @return bool True if type matches 'weight'
     */
    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}