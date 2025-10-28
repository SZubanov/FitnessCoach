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
 * MeasurementConversationHandler - Handles body measurement tracking conversations
 *
 * Implements a two-step conversation flow:
 * 1. input_date - User enters measurement date
 * 2. input_value - User enters measurement value in centimeters
 *
 * Validates:
 * - Date format and validity
 * - Numeric value input
 * - Value range (1-300 cm)
 *
 * Usage:
 * This handler is registered with ConversationManager and automatically
 * invoked when conversation type is 'measurement'.
 */
class MeasurementConversationHandler implements ConversationHandler
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

        $measurementType = $context->data['measurement_type'] ?? 'замер';

        return ConversationResult::continue(
            nextStep: 'input_value',
            message: $this->messageBuilder
                ->create()
                ->icon('📏')
                ->title("Замер: {$measurementType}")
                ->text("Введите значение в сантиметрах:")
                ->addExample('Например: 95')
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
     * Validates the measurement value, saves to database, and completes conversation.
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

        // Validate numeric input
        if (!is_numeric($valueInput)) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Неверное значение')
                    ->text('Введите число (в сантиметрах):')
                    ->addExample('Например: 95')
                    ->build()
            );
        }

        $value = (float) $valueInput;

        // Validate range (1-300 cm)
        if ($value < 1 || $value > 300) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Значение вне допустимого диапазона')
                    ->text('Введите значение от 1 до 300 см:')
                    ->build()
            );
        }

        $measurementType = $context->data['measurement_type'] ?? 'замер';
        $date = $context->data['date'];

        // TODO: Phase 5 - Replace with SaveMeasurement action
        // $measurement = $this->saveMeasurementAction->handle([
        //     'user_id' => $context->user->id,
        //     'type' => $measurementType,
        //     'value' => $value,
        //     'date' => $date,
        // ]);

        // Temporary: Just format success message with data we have
        return ConversationResult::complete(
            message: $this->messageBuilder
                ->create()
                ->icon('✅')
                ->title('Замер сохранен')
                ->addField('Тип', $measurementType)
                ->addField('Значение', "{$value} см")
                ->addField('Дата', $date)
                ->build(),
            keyboard: $this->keyboardFactory->mainMenu()
        );
    }

    /**
     * Get the conversation type identifier
     *
     * @return string 'measurement'
     */
    public function getType(): string
    {
        return 'measurement';
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
     * @return bool True if type matches 'measurement'
     */
    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}