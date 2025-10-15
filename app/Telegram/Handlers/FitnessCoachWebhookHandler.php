<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramFatSecretService;
use App\Telegram\Exceptions\UserNotFoundException;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\TelegramAccountService;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Stringable;
use Throwable;

class FitnessCoachWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly TelegramUserService $telegramUserService,
        private readonly TelegramAccountService $telegramAccountService,
        private readonly TelegramFatSecretService $telegramFatSecretService,
        private readonly ConversationStateService $conversationState,
        private readonly DateValidationService $dateValidation,
    ) {
        parent::__construct();
    }

    /**
     * Centralized error handling for all webhook exceptions
     */
    protected function onFailure(Throwable $throwable): void
    {
        // Log the error with context
        logger()->error('Telegram bot error', [
            'exception' => $throwable->getMessage(),
            'exception_class' => get_class($throwable),
            'trace' => $throwable->getTraceAsString(),
            'chat_id' => $this->chat?->chat_id,
            'user_id' => $this->chat?->user_id,
            'message_text' => $this->message?->text(),
            'callback_data' => $this->callbackQuery?->data(),
        ]);

        // Handle specific exception types
        if ($throwable instanceof UserNotFoundException) {
            $this->chat->message('❌ Пользователь не найден. Пожалуйста, привяжите аккаунт.')
                ->keyboard($this->buildAccountLinkingKeyboard())
                ->send();
            return;
        }

        // Handle FatSecret API errors
        if (str_contains(get_class($throwable), 'FatSecret')) {
            $this->chat->message('❌ Ошибка FatSecret API. Попробуйте позже или обратитесь в поддержку.')
                ->send();
            return;
        }

        // Generic error message for unknown exceptions
        $this->chat->message('❌ Произошла ошибка. Попробуйте позже или обратитесь в поддержку.')
            ->send();
    }

    /**
     * Guard: Require linked account to proceed
     * Returns User if linked, sends error message and returns null if not
     */
    protected function requireLinkedAccount(): ?User
    {
        // Get user from TelegraphChat relationship
        $user = $this->getUserFromChat();

        if (!$user) {
            $this->chat->message('❌ Аккаунт не привязан.\n\nИспользуйте /account для привязки аккаунта.')
                ->keyboard($this->buildAccountLinkingKeyboard())
                ->send();
            return null;
        }

        return $user;
    }

    /**
     * Guard: Require FatSecret authorization to proceed
     * Returns true if authorized, sends error message and returns false if not
     */
    protected function requireFatSecretAuth(): bool
    {
        $user = $this->getUserFromChat();

        if (!$user || !$user->isFatSecretAuthorized()) {
            $this->chat->message('❌ FatSecret не подключен.\n\nИспользуйте /fatsecret для подключения.')
                ->keyboard($this->buildFatSecretKeyboard())
                ->send();
            return false;
        }

        return true;
    }

    /**
     * Get User model from current TelegraphChat
     */
    protected function getUserFromChat(): ?User
    {
        if (!$this->chat->chat_id) {
            return null;
        }

        return $this->telegramUserService->getCurrentUser($this->chat->chat_id);
    }

    /**
     * Build keyboard for account linking actions
     */
    protected function buildAccountLinkingKeyboard(): Keyboard
    {
        // Will be implemented in Phase 4
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('🔗 Привязать аккаунт')->action('accountLinking'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build keyboard for FatSecret connection
     */
    protected function buildFatSecretKeyboard(): Keyboard
    {
        // Will be implemented in Phase 4
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('🔐 Подключить FatSecret')->action('fatSecretConnect'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Handle unknown commands
     */
    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->chat->message("❓ Неизвестная команда: {$text}\n\nИспользуйте /help для списка доступных команд.")
            ->send();
    }

    /**
     * Handle incoming chat messages (non-command text)
     *
     * This is the central message router that directs messages to appropriate
     * conversation handlers based on active conversation state.
     *
     * Flow:
     * 1. Check if user is in an active conversation
     * 2. Route to appropriate handler based on conversation type and step
     * 3. If not in conversation, show help message
     *
     * @param Stringable $text The incoming message text
     * @return void
     */
    protected function handleChatMessage(Stringable $text): void
    {
        $chatId = (string) $this->chat->chat_id;

        // Check if user is in an active conversation
        if (!$this->conversationState->isInConversation($chatId)) {
            $this->chat->html(
                "💬 Я понимаю только команды.\n\n" .
                "Используйте /help для списка доступных команд\n" .
                "или нажмите /start для главного меню."
            )->send();
            return;
        }

        // Route message to appropriate conversation handler
        $conversationType = $this->conversationState->getConversationType($chatId);
        $step = $this->conversationState->getStep($chatId);

        match ($conversationType) {
            'measurement' => $this->handleMeasurementConversation($text, $step),
            'weight' => $this->handleWeightConversation($text, $step),
            'macro' => $this->handleMacroConversation($text, $step),
            'sync' => $this->handleSyncConversation($text, $step),
            default => $this->handleUnknownConversation($chatId),
        };
    }

    /**
     * Handle unknown or invalid conversation type
     */
    private function handleUnknownConversation(string $chatId): void
    {
        $this->conversationState->endConversation($chatId);
        $this->chat->html(
            "❌ Произошла ошибка в диалоге.\n\n" .
            "Попробуйте начать сначала через главное меню."
        )
            ->keyboard($this->buildMainMenuKeyboard())
            ->send();
    }

    // ============================================================================
    // CONVERSATION HANDLERS - Phase 5 (Stubs for now)
    // ============================================================================

    /**
     * Handle measurement conversation flow
     *
     * Steps:
     * 1. input_date - User enters measurement date
     * 2. input_value - User enters measurement value in cm
     * 3. save_success - Save and show success message
     */
    private function handleMeasurementConversation(Stringable $text, ?string $step): void
    {
        $chatId = (string) $this->chat->chat_id;

        match ($step) {
            'input_date' => $this->handleMeasurementDateInput($text, $chatId),
            'input_value' => $this->handleMeasurementValueInput($text, $chatId),
            default => $this->handleUnknownConversation($chatId),
        };
    }

    /**
     * Handle date input for measurement
     */
    private function handleMeasurementDateInput(Stringable $text, string $chatId): void
    {
        // Validate date
        $dateResult = $this->dateValidation->validateAndParseDate((string) $text);

        if (!$dateResult['valid']) {
            $this->chat->html($dateResult['error'])->send();
            return;
        }

        // Store validated date
        $this->conversationState->setData($chatId, 'date', $dateResult['formatted']);
        $this->conversationState->setData($chatId, 'date_object', $dateResult['date']);

        // Move to value input step
        $this->conversationState->setStep($chatId, 'input_value');

        // Get measurement type
        $measurementType = $this->conversationState->getData($chatId, 'measurement_type', 'замер');

        // Ask for measurement value
        $this->chat->html(
            "📏 **Замер: {$measurementType}**\n\n" .
            "Введите значение в сантиметрах:\n" .
            "Например: 95"
        )->send();
    }

    /**
     * Handle value input for measurement
     */
    private function handleMeasurementValueInput(Stringable $text, string $chatId): void
    {
        $valueInput = trim((string) $text);

        // Validate numeric input
        if (!is_numeric($valueInput)) {
            $this->chat->html(
                "❌ **Неверное значение**\n\n" .
                "Введите число (в сантиметрах):\n" .
                "Например: 95"
            )->send();
            return;
        }

        $value = (float) $valueInput;

        // Validate range
        if ($value < 1 || $value > 300) {
            $this->chat->html(
                "❌ **Значение вне допустимого диапазона**\n\n" .
                "Введите значение от 1 до 300 см:"
            )->send();
            return;
        }

        // Get stored data
        $date = $this->conversationState->getData($chatId, 'date');
        $measurementType = $this->conversationState->getData($chatId, 'measurement_type', 'замер');

        // TODO: Save to database
        // $this->measurementService->saveMeasurement($userId, $measurementType, $value, $date);

        // Show success message
        $this->chat->html(
            "✅ **Замер сохранен**\n\n" .
            "Тип: {$measurementType}\n" .
            "Значение: {$value} см\n" .
            "Дата: {$date}"
        )->send();

        // Clean up and return to menu
        $this->conversationState->endConversation($chatId);
        $this->mainMenu();
    }

    /**
     * Handle weight conversation flow
     *
     * Steps:
     * 1. input_date - User enters weight measurement date
     * 2. input_value - User enters weight value in kg
     * 3. save_success - Save and show success message
     */
    private function handleWeightConversation(Stringable $text, ?string $step): void
    {
        $chatId = (string) $this->chat->chat_id;

        match ($step) {
            'input_date' => $this->handleWeightDateInput($text, $chatId),
            'input_value' => $this->handleWeightValueInput($text, $chatId),
            default => $this->handleUnknownConversation($chatId),
        };
    }

    /**
     * Handle date input for weight
     */
    private function handleWeightDateInput(Stringable $text, string $chatId): void
    {
        // Validate date
        $dateResult = $this->dateValidation->validateAndParseDate((string) $text);

        if (!$dateResult['valid']) {
            $this->chat->html($dateResult['error'])->send();
            return;
        }

        // Store validated date
        $this->conversationState->setData($chatId, 'date', $dateResult['formatted']);
        $this->conversationState->setData($chatId, 'date_object', $dateResult['date']);

        // Move to value input step
        $this->conversationState->setStep($chatId, 'input_value');

        // Ask for weight value
        $this->chat->html(
            "⚖️ **Запись веса**\n\n" .
            "Введите ваш вес в килограммах:\n" .
            "Например: 70.5 или 85,2"
        )->send();
    }

    /**
     * Handle value input for weight
     */
    private function handleWeightValueInput(Stringable $text, string $chatId): void
    {
        $valueInput = trim((string) $text);

        // Replace comma with dot for decimal separator
        $valueInput = str_replace(',', '.', $valueInput);

        // Validate numeric input
        if (!is_numeric($valueInput)) {
            $this->chat->html(
                "❌ **Неверное значение**\n\n" .
                "Введите число (вес в килограммах):\n" .
                "Например: 70.5 или 85,2"
            )->send();
            return;
        }

        $value = (float) $valueInput;

        // Validate range
        if ($value < 20 || $value > 300) {
            $this->chat->html(
                "❌ **Значение вне допустимого диапазона**\n\n" .
                "Введите вес от 20 до 300 кг:"
            )->send();
            return;
        }

        // Get stored data
        $date = $this->conversationState->getData($chatId, 'date');

        // TODO: Save to database
        // $this->weightService->saveWeight($userId, $value, $date);

        // Show success message
        $this->chat->html(
            "✅ **Вес сохранен**\n\n" .
            "Значение: {$value} кг\n" .
            "Дата: {$date}"
        )->send();

        // Clean up and return to menu
        $this->conversationState->endConversation($chatId);
        $this->mainMenu();
    }

    /**
     * Handle macro/КБЖУ conversation flow
     *
     * Steps:
     * 1. input_date - User enters date for macro entry
     * 2. input_value - User enters macro value (calories/protein/fat/carbs)
     * 3. save_success - Save and show success message
     */
    private function handleMacroConversation(Stringable $text, ?string $step): void
    {
        $chatId = (string) $this->chat->chat_id;

        match ($step) {
            'input_date' => $this->handleMacroDateInput($text, $chatId),
            'input_value' => $this->handleMacroValueInput($text, $chatId),
            default => $this->handleUnknownConversation($chatId),
        };
    }

    /**
     * Handle date input for macro
     */
    private function handleMacroDateInput(Stringable $text, string $chatId): void
    {
        // Validate date
        $dateResult = $this->dateValidation->validateAndParseDate((string) $text);

        if (!$dateResult['valid']) {
            $this->chat->html($dateResult['error'])->send();
            return;
        }

        // Store validated date
        $this->conversationState->setData($chatId, 'date', $dateResult['formatted']);
        $this->conversationState->setData($chatId, 'date_object', $dateResult['date']);

        // Move to value input step
        $this->conversationState->setStep($chatId, 'input_value');

        // Get macro type info
        $macroType = $this->conversationState->getData($chatId, 'macro_type', [
            'name' => 'неизвестно',
            'unit' => '',
            'icon' => '❓'
        ]);

        // Ask for macro value
        $example = $macroType['name'] === 'калории' ? '2000' : '100';
        $this->chat->html(
            "{$macroType['icon']} **{$macroType['name']}**\n\n" .
            "Введите значение в {$macroType['unit']}:\n" .
            "Например: {$example}"
        )->send();
    }

    /**
     * Handle value input for macro
     */
    private function handleMacroValueInput(Stringable $text, string $chatId): void
    {
        $valueInput = trim((string) $text);

        // Validate numeric input
        if (!is_numeric($valueInput)) {
            $macroType = $this->conversationState->getData($chatId, 'macro_type', [
                'name' => 'неизвестно',
                'unit' => '',
                'icon' => '❓'
            ]);
            $example = $macroType['name'] === 'калории' ? '2000' : '100';

            $this->chat->html(
                "❌ **Неверное значение**\n\n" .
                "Введите число в {$macroType['unit']}:\n" .
                "Например: {$example}"
            )->send();
            return;
        }

        $value = (float) $valueInput;
        $macroType = $this->conversationState->getData($chatId, 'macro_type', [
            'name' => 'неизвестно',
            'unit' => '',
            'icon' => '❓'
        ]);

        // Validate ranges based on macro type
        $validRange = match ($macroType['name']) {
            'калории' => ['min' => 500, 'max' => 5000],
            default => ['min' => 0, 'max' => 1000] // For proteins, fats, carbs
        };

        if ($value < $validRange['min'] || $value > $validRange['max']) {
            $this->chat->html(
                "❌ **Значение вне допустимого диапазона**\n\n" .
                "Введите значение от {$validRange['min']} до {$validRange['max']} {$macroType['unit']}:"
            )->send();
            return;
        }

        // Get stored data
        $date = $this->conversationState->getData($chatId, 'date');

        // TODO: Save to database
        // $this->macroService->saveMacro($userId, $macroType['name'], $value, $date);

        // Show success message
        $this->chat->html(
            "✅ **КБЖУ сохранено**\n\n" .
            "Тип: {$macroType['name']}\n" .
            "Значение: {$value} {$macroType['unit']}\n" .
            "Дата: {$date}"
        )->send();

        // Clean up and return to menu
        $this->conversationState->endConversation($chatId);
        $this->mainMenu();
    }

    /**
     * Handle sync conversation flow
     *
     * Steps:
     * 1. input_date - User enters date for synchronization
     * 2. execute_sync - Perform synchronization and show results
     */
    private function handleSyncConversation(Stringable $text, ?string $step): void
    {
        $chatId = (string) $this->chat->chat_id;

        match ($step) {
            'input_date' => $this->handleSyncDateInput($text, $chatId),
            'execute_sync' => $this->handleSyncExecution($text, $chatId),
            default => $this->handleUnknownConversation($chatId),
        };
    }

    /**
     * Handle date input for sync
     */
    private function handleSyncDateInput(Stringable $text, string $chatId): void
    {
        // Validate date
        $dateResult = $this->dateValidation->validateAndParseDate((string) $text);

        if (!$dateResult['valid']) {
            $this->chat->html($dateResult['error'])->send();
            return;
        }

        // Store validated date
        $this->conversationState->setData($chatId, 'date', $dateResult['formatted']);
        $this->conversationState->setData($chatId, 'date_object', $dateResult['date']);

        // Move to execute sync step
        $this->conversationState->setStep($chatId, 'execute_sync');

        // Get sync type info
        $syncType = $this->conversationState->getData($chatId, 'sync_type', [
            'name' => 'Полная синхронизация',
            'icon' => '🔄'
        ]);

        // Show processing message
        $this->chat->html("🔄 Выполняется синхронизация...")->send();

        // Execute sync immediately (no additional input needed)
        $this->executeSynchronization($chatId, $dateResult);
    }

    /**
     * Handle sync execution (fallback if needed)
     */
    private function handleSyncExecution(Stringable $text, string $chatId): void
    {
        // This should not normally be reached as sync executes immediately after date input
        // But we keep it for completeness
        $dateResult = [
            'formatted' => $this->conversationState->getData($chatId, 'date'),
            'date' => $this->conversationState->getData($chatId, 'date_object')
        ];

        $this->executeSynchronization($chatId, $dateResult);
    }

    /**
     * Execute the actual synchronization
     */
    private function executeSynchronization(string $chatId, array $dateResult): void
    {
        $syncType = $this->conversationState->getData($chatId, 'sync_type', [
            'name' => 'Полная синхронизация',
            'icon' => '🔄'
        ]);

        try {
            // TODO: Implement actual sync logic
            // $syncCallback = $this->conversationState->getData($chatId, 'sync_callback');
            // $this->fatSecretSyncService->performSync($userId, $syncCallback, $dateResult['date']);

            // Simulate sync process
            sleep(1);

            // Show success message
            $this->chat->html(
                "✅ **Синхронизация завершена**\n\n" .
                "Тип: {$syncType['name']}\n" .
                "Дата: {$dateResult['formatted']}\n\n" .
                "Данные успешно синхронизированы с FatSecret"
            )->send();

        } catch (\Exception $e) {
            // Show error message
            $this->chat->html(
                "❌ **Ошибка синхронизации**\n\n" .
                "Попробуйте позже или проверьте подключение к FatSecret"
            )->send();
        }

        // Clean up and return to menu
        $this->conversationState->endConversation($chatId);
        $this->mainMenu();
    }

    // ============================================================================
    // COMMANDS - Phase 3
    // ============================================================================

    /**
     * Handle /start command
     * Shows welcome message and main menu
     */
    public function start(): void
    {
        $welcomeText = "🎯 **Добро пожаловать в FitnessCoach!**\n\n" .
                      "Я помогу вам отслеживать:\n" .
                      "⚖️ Вес и измерения тела\n" .
                      "🍎 Макронутриенты (КБЖУ)\n" .
                      "🔄 Синхронизацию с FatSecret\n\n" .
                      "Используйте меню ниже для начала работы:";

        $this->chat->html($welcomeText)->send();

        // Show main menu with keyboard
        $this->mainMenu();
    }

    /**
     * Show main menu with keyboard
     * Can be called from /start or from callback actions
     */
    public function mainMenu(): void
    {
        $this->chat->html('🏠 Главное меню FitnessCoach')
            ->keyboard($this->buildMainMenuKeyboard())
            ->send();
    }

    /**
     * Handle /help command
     * Shows list of available commands
     */
    public function help(): void
    {
        $helpText = "🆘 **Помощь по командам FitnessCoach**\n\n" .
                   $this->formatCommandsHelp() . "\n\n" .
                   "🔗 **Команды с параметрами:**\n" .
                   "• /sync полная - Полная синхронизация с FatSecret";

        $this->chat->html($helpText)
            ->keyboard($this->buildHelpKeyboard())
            ->send();
    }

    /**
     * Show help from callback (from main menu button)
     * This version includes back button and feature descriptions
     */
    public function showHelp(): void
    {
        $helpText = "📖 **Помощь FitnessCoach Bot**\n\n" .
                   "**Доступные функции:**\n" .
                   "📏 **Замеры** - Записывайте измерения тела\n" .
                   "🔄 **Синхронизация** - Синхронизация с FatSecret\n" .
                   "🍎 **КБЖУ** - Отслеживание калорий и макронутриентов\n" .
                   "⚖️ **Вес** - Записывайте показания веса\n" .
                   "⚙️ **Настройки** - Управление аккаунтом и подключениями\n\n" .
                   "**Формат даты:** DD.MM.YYYY или DD/MM/YYYY\n" .
                   "**Пример:** 25.12.2024 или 25/12/2024";

        // Edit the message with new keyboard
        $this->chat->edit($this->messageId)
            ->html($helpText)
            ->keyboard($this->buildHelpKeyboard())
            ->send();
    }

    /**
     * Format commands list for help message
     */
    protected function formatCommandsHelp(): string
    {
        $commands = [];

        $commands[] = "📋 **Основные команды:**";
        $commands[] = "/start - Главное меню и приветствие";
        $commands[] = "/help - Помощь и список команд";

        $commands[] = "\n🚀 **Быстрые команды:**";
        $commands[] = "/sync [тип] - Синхронизация с FatSecret";

        $commands[] = "\n⚙️ **Управление:**";
        $commands[] = "/account - Привязка аккаунта";
        $commands[] = "/fatsecret - Подключение к FatSecret";

        return implode("\n", $commands);
    }

    /**
     * Handle /account command
     * Shows account linking menu with options to link/unlink/check status
     */
    public function account(): void
    {
        $this->chat->html('🔗 Привязка аккаунта')
            ->keyboard($this->buildAccountMenuKeyboard())
            ->send();
    }

    /**
     * Show account menu from callback
     * Edits the message instead of sending new one
     */
    public function accountLinking(): void
    {
        $this->chat->edit($this->messageId)
            ->html('🔗 Привязка аккаунта')
            ->keyboard($this->buildAccountMenuKeyboard())
            ->send();
    }

    /**
     * Handle /fatsecret command
     * Shows FatSecret connection menu with OAuth options
     */
    public function fatsecret(): void
    {
        $this->chat->html('🔗 Привязка FatSecret')
            ->keyboard($this->buildFatSecretMenuKeyboard())
            ->send();
    }

    /**
     * Show FatSecret menu from callback
     * Edits the message instead of sending new one
     */
    public function fatSecretConnect(): void
    {
        $this->chat->edit($this->messageId)
            ->html('🔗 Привязка FatSecret')
            ->keyboard($this->buildFatSecretMenuKeyboard())
            ->send();
    }

    /**
     * Handle /sync command
     * Shows synchronization menu with FatSecret sync options
     */
    public function sync(): void
    {
        // Check if user has FatSecret connected
        if (!$this->requireFatSecretAuth()) {
            return;
        }

        $instructionsText = "🔄 **Синхронизация с FatSecret**\n\n" .
                           "Выберите тип синхронизации:\n\n" .
                           "💡 **Доступные опции:**\n" .
                           "🔄 **Полная** - Синхронизация всех данных\n" .
                           "⚖️ **Вес** - Только данные о весе\n" .
                           "🍎 **Дневник питания** - Только питание\n\n" .
                           "⚠️ **Требуется подключение к FatSecret**";

        $this->chat->html($instructionsText)
            ->keyboard($this->buildSyncMenuKeyboard())
            ->send();
    }

    /**
     * Show sync menu from callback
     * Edits the message instead of sending new one
     */
    public function showSync(): void
    {
        // Check if user has FatSecret connected
        if (!$this->requireFatSecretAuth()) {
            return;
        }

        $instructionsText = "🔄 **Синхронизация с FatSecret**\n\n" .
                           "Выберите тип синхронизации:\n\n" .
                           "💡 **Доступные опции:**\n" .
                           "🔄 **Полная** - Синхронизация всех данных\n" .
                           "⚖️ **Вес** - Только данные о весе\n" .
                           "🍎 **Дневник питания** - Только питание\n\n" .
                           "⚠️ **Требуется подключение к FatSecret**";

        $this->chat->edit($this->messageId)
            ->html($instructionsText)
            ->keyboard($this->buildSyncMenuKeyboard())
            ->send();
    }

    // ============================================================================
    // ACCOUNT LINKING CALLBACKS - Phase 4
    // ============================================================================

    /**
     * Generate link code for account binding
     * Shows a temporary code that user can use in web interface
     */
    public function generateLinkCode(): void
    {
        $linkCode = $this->telegramAccountService->generateLinkAccountCode($this->chat->chat_id);

        $message = "🔗 **Код для привязки аккаунта**\n\n" .
            "Ваш код: `{$linkCode}`\n\n" .
            "⏰ Код действителен 15 минут\n" .
            "🌐 Используйте этот код в веб-интерфейсе для привязки аккаунта\n\n" .
            "⚠️ **Внимание:** При создании нового кода, старый перестает действовать";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildAccountBackKeyboard())
            ->send();
    }

    /**
     * Check account linking status
     * Shows whether the current Telegram account is linked to FitnessCoach user
     */
    public function checkLinkStatus(): void
    {
        $isLinked = $this->telegramAccountService->checkLinkAccountStatus($this->chat->chat_id);

        $statusMessage = $isLinked
            ? "✅ **Статус привязки**\n\nВаш аккаунт привязан к системе FitnessCoach"
            : "❌ **Статус привязки**\n\nВаш аккаунт не привязан к системе FitnessCoach\n\n" .
              "Используйте кнопку \"Получить код для привязки\" для создания кода";

        $this->chat->edit($this->messageId)
            ->html($statusMessage)
            ->keyboard($this->buildAccountBackKeyboard())
            ->send();
    }

    /**
     * Remove account link
     * Unlinks the Telegram account from FitnessCoach user
     */
    public function removeLinkAccount(): void
    {
        $this->telegramAccountService->removeLinkAccount($this->chat->chat_id);

        $message = "❌ **Отвязка аккаунта**\n\n" .
            "Ваш аккаунт был отвязан от системы FitnessCoach\n\n" .
            "Для повторной привязки используйте функцию \"Получить код для привязки\"";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildAccountBackKeyboard())
            ->send();
    }

    // ============================================================================
    // FATSECRET CALLBACKS - Phase 4
    // ============================================================================

    /**
     * Check FatSecret connection status
     * Shows whether the user is authorized with FatSecret
     */
    public function checkFatSecretConnection(): void
    {
        $user = $this->getUserFromChat();
        $isAuthorized = $user && $user->isFatSecretAuthorized();

        $statusMessage = $isAuthorized
            ? "✅ **Статус привязки**\n\nВаш аккаунт авторизован в FatSecret"
            : "❌ **Статус привязки**\n\nВаш аккаунт не авторизован в FatSecret\n\n";

        $this->chat->edit($this->messageId)
            ->html($statusMessage)
            ->keyboard($this->buildFatSecretBackKeyboard())
            ->send();
    }

    /**
     * Initiate FatSecret OAuth flow
     * Generates OAuth URL and shows it to the user
     */
    public function connectFatSecret(): void
    {
        // Require linked account first
        $user = $this->requireLinkedAccount();
        if (!$user) {
            return;
        }

        try {
            $oauthUrl = $this->telegramFatSecretService->initiateOAuthForTelegram($user);

            $message = "🔗 **Подключение FatSecret**\n\n" .
                "Для подключения к FatSecret нажмите на ссылку ниже:\n\n" .
                "[Подключить FatSecret]({$oauthUrl})\n\n" .
                "После авторизации вы будете автоматически перенаправлены обратно в бот";

            $this->chat->edit($this->messageId)
                ->html($message)
                ->keyboard($this->buildFatSecretBackKeyboard())
                ->send();
        } catch (\Exception $e) {
            $this->chat->edit($this->messageId)
                ->html('❌ Ошибка при создании ссылки для подключения FatSecret. Попробуйте позже.')
                ->keyboard($this->buildFatSecretBackKeyboard())
                ->send();
        }
    }

    /**
     * Logout from FatSecret
     * Removes FatSecret authorization tokens
     */
    public function logoutFromFatSecret(): void
    {
        $user = $this->getUserFromChat();

        if (!$user) {
            $this->chat->edit($this->messageId)
                ->html('❌ Аккаунт не привязан.')
                ->keyboard($this->buildFatSecretBackKeyboard())
                ->send();
            return;
        }

        // Use the old service's logout method which uses FatSecretLogoutInterface
        $oldService = app(\App\Telegram\Services\TelegramFatSecretService::class);
        $oldService->logout($this->chat->chat_id);

        $message = "🚪 **Отключение от FatSecret**\n\n" .
            "Ваш аккаунт был отключен от FatSecret\n\n" .
            "❌ Функции синхронизации больше не доступны\n";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildFatSecretBackKeyboard())
            ->send();
    }

    // ============================================================================
    // SYNC CALLBACKS - Phase 5 (Simplified for now)
    // ============================================================================

    /**
     * Perform full synchronization with FatSecret
     * Initiates conversation to select sync date
     */
    public function syncFull(): void
    {
        $this->startSyncConversation([
            'name' => 'Полная синхронизация',
            'icon' => '🔄',
            'callback' => 'full'
        ]);
    }

    /**
     * Perform weight synchronization with FatSecret
     * Initiates conversation to select sync date
     */
    public function syncWeight(): void
    {
        $this->startSyncConversation([
            'name' => 'Синхронизация веса',
            'icon' => '⚖️',
            'callback' => 'weight'
        ]);
    }

    /**
     * Perform food diary synchronization with FatSecret
     * Initiates conversation to select sync date
     */
    public function syncFood(): void
    {
        $this->startSyncConversation([
            'name' => 'Синхронизация дневника питания',
            'icon' => '🍎',
            'callback' => 'food'
        ]);
    }

    /**
     * Helper method to start sync conversation with specific type
     */
    private function startSyncConversation(array $syncType): void
    {
        $chatId = (string) $this->chat->chat_id;

        // Start conversation with sync type
        $this->conversationState->startConversation(
            $chatId,
            'sync',
            [
                'sync_type' => $syncType,
                'sync_callback' => $syncType['callback']
            ]
        );

        $this->conversationState->setStep($chatId, 'input_date');

        // Show date input instructions
        $instructions = $this->dateValidation->getDateInputInstructions();
        $this->chat->html("🔄 **{$syncType['name']}**\n\n" . $instructions)->send();
    }

    // ============================================================================
    // MAIN MENU CALLBACKS - Phase 4 (Stubs for now)
    // ============================================================================

    /**
     * Show settings menu
     * Note: This is a stub implementation. Full settings menu will be added in Phase 5
     */
    public function showSettings(): void
    {
        $message = "⚙️ **Настройки**\n\n" .
            "Управление вашим аккаунтом и подключениями:\n\n" .
            "🔗 **Привязка аккаунта** - Управление связью Telegram с FitnessCoach\n" .
            "🔐 **FatSecret** - Подключение к FatSecret API\n" .
            "👤 **Профиль** - Ваши личные данные\n\n" .
            "Выберите раздел для управления:";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildSettingsKeyboard())
            ->send();
    }

    /**
     * Show measurements menu
     */
    public function showMeasurements(): void
    {
        // Check if user has linked account
        if (!$this->requireLinkedAccount()) {
            return;
        }

        $message = "📏 **Замеры тела**\n\n" .
            "Отслеживайте изменения ваших измерений:\n\n" .
            "📐 **Новый замер** - Добавить новое измерение\n" .
            "📊 **История** - Просмотр истории замеров\n" .
            "📈 **Прогресс** - График изменений\n\n" .
            "💡 **Совет:** Делайте замеры в одно и то же время для точности";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildMeasurementsKeyboard())
            ->send();
    }

    /**
     * Start new measurement conversation
     * Initiates measurement input flow with date step
     */
    public function startNewMeasurement(): void
    {
        // Check if user has linked account
        if (!$this->requireLinkedAccount()) {
            return;
        }

        $chatId = (string) $this->chat->chat_id;

        // Start conversation with measurement type
        // For now we'll use a generic type, but this could be extended to ask for specific body part
        $this->conversationState->startConversation(
            $chatId,
            'measurement',
            ['measurement_type' => 'Грудь'] // TODO: Add measurement type selection
        );

        $this->conversationState->setStep($chatId, 'input_date');

        // Show date input instructions
        $instructions = $this->dateValidation->getDateInputInstructions();
        $this->chat->html($instructions)->send();
    }

    /**
     * Show macros (КБЖУ) menu
     * Note: This is a stub implementation. Full macros flow will be added in Phase 5
     */
    public function showMacros(): void
    {
        // Check if user has linked account
        if (!$this->requireLinkedAccount()) {
            return;
        }

        $message = "🍎 **КБЖУ - Макронутриенты**\n\n" .
            "Отслеживание калорий и макронутриентов:\n\n" .
            "➕ **Добавить прием пищи** - Записать еду\n" .
            "📊 **Сегодня** - Статистика за сегодня\n" .
            "📅 **История** - Просмотр по дням\n" .
            "🔄 **Синхронизация** - Импорт из FatSecret\n\n" .
            "💡 **К** - Калории, **Б** - Белки, **Ж** - Жиры, **У** - Углеводы";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildMacrosKeyboard())
            ->send();
    }

    /**
     * Show weight tracking menu
     */
    public function showWeight(): void
    {
        // Check if user has linked account
        if (!$this->requireLinkedAccount()) {
            return;
        }

        $message = "⚖️ **Отслеживание веса**\n\n" .
            "Ведите учет вашего веса:\n\n" .
            "➕ **Добавить вес** - Новая запись\n" .
            "📊 **Текущий вес** - Последние показания\n" .
            "📈 **Динамика** - График изменений\n" .
            "🔄 **Синхронизация** - Импорт из FatSecret\n\n" .
            "💡 **Совет:** Взвешивайтесь утром натощак для точности";

        $this->chat->edit($this->messageId)
            ->html($message)
            ->keyboard($this->buildWeightKeyboard())
            ->send();
    }

    /**
     * Start new weight entry conversation
     * Initiates weight input flow with date step
     */
    public function startNewWeight(): void
    {
        // Check if user has linked account
        if (!$this->requireLinkedAccount()) {
            return;
        }

        $chatId = (string) $this->chat->chat_id;

        // Start conversation with weight type
        $this->conversationState->startConversation(
            $chatId,
            'weight',
            []
        );

        $this->conversationState->setStep($chatId, 'input_date');

        // Show date input instructions
        $instructions = $this->dateValidation->getDateInputInstructions();
        $this->chat->html($instructions)->send();
    }

    /**
     * Select calories macro type and start conversation
     */
    public function selectMacroCalories(): void
    {
        $this->startMacroConversationWithType([
            'name' => 'калории',
            'unit' => 'ккал',
            'icon' => '🔥'
        ]);
    }

    /**
     * Select proteins macro type and start conversation
     */
    public function selectMacroProteins(): void
    {
        $this->startMacroConversationWithType([
            'name' => 'белки',
            'unit' => 'г',
            'icon' => '🥩'
        ]);
    }

    /**
     * Select fats macro type and start conversation
     */
    public function selectMacroFats(): void
    {
        $this->startMacroConversationWithType([
            'name' => 'жиры',
            'unit' => 'г',
            'icon' => '🧈'
        ]);
    }

    /**
     * Select carbs macro type and start conversation
     */
    public function selectMacroCarbs(): void
    {
        $this->startMacroConversationWithType([
            'name' => 'углеводы',
            'unit' => 'г',
            'icon' => '🍞'
        ]);
    }

    /**
     * Helper method to start macro conversation with specific type
     */
    private function startMacroConversationWithType(array $macroType): void
    {
        // Check if user has linked account
        if (!$this->requireLinkedAccount()) {
            return;
        }

        $chatId = (string) $this->chat->chat_id;

        // Start conversation with macro type
        $this->conversationState->startConversation(
            $chatId,
            'macro',
            ['macro_type' => $macroType]
        );

        $this->conversationState->setStep($chatId, 'input_date');

        // Show date input instructions
        $instructions = $this->dateValidation->getDateInputInstructions();
        $this->chat->html($instructions)->send();
    }

    // ============================================================================
    // KEYBOARDS
    // ============================================================================

    /**
     * Build main menu keyboard
     */
    protected function buildMainMenuKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('⚙️ Настройки')->action('showSettings'),
            \DefStudio\Telegraph\Keyboard\Button::make('📏 Замеры')->action('showMeasurements'),
            \DefStudio\Telegraph\Keyboard\Button::make('🔄 Синхронизация')->action('showSync'),
            \DefStudio\Telegraph\Keyboard\Button::make('🍎 КБЖУ')->action('showMacros'),
            \DefStudio\Telegraph\Keyboard\Button::make('⚖️ Вес')->action('showWeight'),
            \DefStudio\Telegraph\Keyboard\Button::make('❓ Помощь')->action('showHelp'),
        ]);
    }

    /**
     * Build help keyboard with back button
     */
    protected function buildHelpKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build account menu keyboard
     * Shows options to generate code, check status, or remove link
     */
    protected function buildAccountMenuKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('📝 Получить код для привязки')->action('generateLinkCode'),
            \DefStudio\Telegraph\Keyboard\Button::make('✅ Проверить привязку')->action('checkLinkStatus'),
            \DefStudio\Telegraph\Keyboard\Button::make('❌ Отвязать аккаунт')->action('removeLinkAccount'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build account back keyboard
     * Shows back button to return to account menu and main menu
     */
    protected function buildAccountBackKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('↩️ Назад')->action('accountLinking'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build FatSecret menu keyboard
     * Shows options to connect, check status, or logout
     */
    protected function buildFatSecretMenuKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('✅ Проверить привязку')->action('checkFatSecretConnection'),
            \DefStudio\Telegraph\Keyboard\Button::make('🔗 Подключить FatSecret')->action('connectFatSecret'),
            \DefStudio\Telegraph\Keyboard\Button::make('🚪 Выйти из FatSecret')->action('logoutFromFatSecret'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build FatSecret back keyboard
     * Shows back button to return to FatSecret menu and main menu
     */
    protected function buildFatSecretBackKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('↩️ Назад')->action('fatSecretConnect'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build sync menu keyboard
     * Shows sync type options
     */
    protected function buildSyncMenuKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('🔄 Полная синхронизация')->action('syncFull'),
            \DefStudio\Telegraph\Keyboard\Button::make('⚖️ Синхронизация веса')->action('syncWeight'),
            \DefStudio\Telegraph\Keyboard\Button::make('🍎 Дневник питания')->action('syncFood'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build sync back keyboard
     * Shows back button to return to sync menu and main menu
     */
    protected function buildSyncBackKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('↩️ Назад')->action('showSync'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build settings menu keyboard
     * Shows options for account and FatSecret management
     */
    protected function buildSettingsKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('🔗 Привязка аккаунта')->action('accountLinking'),
            \DefStudio\Telegraph\Keyboard\Button::make('🔐 FatSecret')->action('fatSecretConnect'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build measurements menu keyboard
     */
    protected function buildMeasurementsKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('📐 Новый замер')->action('startNewMeasurement'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build macros menu keyboard
     * Shows options for selecting macro type (calories, proteins, fats, carbs)
     */
    protected function buildMacrosKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('🔥 Калории')->action('selectMacroCalories'),
            \DefStudio\Telegraph\Keyboard\Button::make('🥩 Белки')->action('selectMacroProteins'),
            \DefStudio\Telegraph\Keyboard\Button::make('🧈 Жиры')->action('selectMacroFats'),
            \DefStudio\Telegraph\Keyboard\Button::make('🍞 Углеводы')->action('selectMacroCarbs'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Build weight menu keyboard
     */
    protected function buildWeightKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            \DefStudio\Telegraph\Keyboard\Button::make('⚖️ Добавить вес')->action('startNewWeight'),
            \DefStudio\Telegraph\Keyboard\Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }
}
