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
 * MacroConversationHandler - Handles macro nutrient (КБЖУ) tracking conversations
 *
 * Implements a two-step conversation flow:
 * 1. input_date - User enters date for macro entry
 * 2. input_value - User enters macro value (calories/protein/fat/carbs)
 *
 * Validates:
 * - Date format and validity
 * - Numeric value input
 * - Value ranges based on macro type:
 *   - Calories: 500-5000 kcal
 *   - Proteins/Fats/Carbs: 0-1000 g
 *
 * Macro Type Context:
 * Expects 'macro_type' in conversation context as array with:
 * - 'name': macro type name (e.g., 'калории', 'белки', 'жиры', 'углеводы')
 * - 'unit': measurement unit (e.g., 'ккал', 'г')
 * - 'icon': emoji icon (e.g., '🔥', '🥩', '🥑', '🌾')
 *
 * Usage:
 * This handler is registered with ConversationManager and automatically
 * invoked when conversation type is 'macro'.
 */
class MacroConversationHandler implements ConversationHandler
{
    /**
     * Default macro type if not provided in context
     */
    private const DEFAULT_MACRO_TYPE = [
        'name' => 'неизвестно',
        'unit' => '',
        'icon' => '❓'
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

        $macroType = $context->data['macro_type'] ?? self::DEFAULT_MACRO_TYPE;
        $example = $macroType['name'] === 'калории' ? '2000' : '100';

        return ConversationResult::continue(
            nextStep: 'input_value',
            message: $this->messageBuilder
                ->create()
                ->icon($macroType['icon'])
                ->title($macroType['name'])
                ->text("Введите значение в {$macroType['unit']}:")
                ->addExample("Например: {$example}")
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
     * Validates the macro value based on type-specific ranges,
     * saves to database, and completes conversation.
     *
     * Validation ranges:
     * - Calories (калории): 500-5000
     * - Others (белки, жиры, углеводы): 0-1000
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
        $macroType = $context->data['macro_type'] ?? self::DEFAULT_MACRO_TYPE;

        // Validate numeric input
        if (!is_numeric($valueInput)) {
            $example = $macroType['name'] === 'калории' ? '2000' : '100';

            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Неверное значение')
                    ->text("Введите число в {$macroType['unit']}:")
                    ->addExample("Например: {$example}")
                    ->build()
            );
        }

        $value = (float) $valueInput;

        // Determine validation range based on macro type
        $validRange = $this->getValidationRange($macroType['name']);

        // Validate range
        if ($value < $validRange['min'] || $value > $validRange['max']) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Значение вне допустимого диапазона')
                    ->text("Введите значение от {$validRange['min']} до {$validRange['max']} {$macroType['unit']}:")
                    ->build()
            );
        }

        $date = $context->data['date'];

        // TODO: Phase 5 - Replace with SaveMacro action
        // $macro = $this->saveMacroAction->handle([
        //     'user_id' => $context->user->id,
        //     'type' => $macroType['name'],
        //     'value' => $value,
        //     'date' => $date,
        // ]);

        // Temporary: Just format success message with data we have
        return ConversationResult::complete(
            message: $this->messageBuilder
                ->create()
                ->icon('✅')
                ->title('КБЖУ сохранено')
                ->addField('Тип', $macroType['name'])
                ->addField('Значение', "{$value} {$macroType['unit']}")
                ->addField('Дата', $date)
                ->build(),
            keyboard: $this->keyboardFactory->mainMenu()
        );
    }

    /**
     * Get validation range for macro type
     *
     * Returns min/max values based on macro type:
     * - Calories: 500-5000
     * - Others (proteins, fats, carbs): 0-1000
     *
     * @param string $macroTypeName Macro type name (e.g., 'калории', 'белки')
     * @return array{min: int, max: int} Validation range
     */
    private function getValidationRange(string $macroTypeName): array
    {
        return match ($macroTypeName) {
            'калории' => ['min' => 500, 'max' => 5000],
            default => ['min' => 0, 'max' => 1000], // Proteins, fats, carbs
        };
    }

    /**
     * Get the conversation type identifier
     *
     * @return string 'macro'
     */
    public function getType(): string
    {
        return 'macro';
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
     * @return bool True if type matches 'macro'
     */
    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}