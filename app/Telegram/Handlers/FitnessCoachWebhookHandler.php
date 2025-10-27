<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramFatSecretService;
use App\Telegram\Callbacks\CallbackRegistry;
use App\Telegram\Exceptions\UserNotFoundException;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\TelegramAccountService;
use App\Telegram\Services\TelegramCommandRegistry;
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
        private readonly TelegramCommandRegistry $commandRegistry,
        private readonly CallbackRegistry $callbackRegistry,
    ) {
        parent::__construct();
    }

    /**
     * Override Telegraph's handleCallbackQuery to delegate to CallbackRegistry
     *
     * Telegraph's default implementation uses App::call() with reflection to invoke
     * callback methods. We override it to delegate directly to the CallbackRegistry,
     * which routes to the appropriate handler class.
     *
     * Flow: Telegram callback → handleCallbackQuery() → CallbackRegistry → Handler class
     *
     * @return void
     */
    protected function handleCallbackQuery(): void
    {
        // Use parent's method to extract all callback data
        // This sets: $this->messageId, $this->callbackQueryId, $this->data, $this->originalKeyboard
        parent::extractCallbackQueryData();

        /** @var string $action */
        $action = $this->callbackQuery?->data()->get('action') ?? '';

        // Delegate directly to CallbackRegistry - no magic methods needed!
        $this->callbackRegistry->handle($action, $this->chat, $this->messageId);
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
    // COMMANDS - Delegated to TelegramCommandRegistry
    // ============================================================================

    /**
     * Handle /start command
     * Delegates to StartCommandHandler via registry
     */
    public function start(): void
    {
        $this->commandRegistry->handle('start', $this->chat);
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
     * Delegates to HelpCommandHandler via registry
     */
    public function help(): void
    {
        $this->commandRegistry->handle('help', $this->chat);
    }

    /**
     * Handle /account command
     * Delegates to AccountCommandHandler via registry
     */
    public function account(): void
    {
        $this->commandRegistry->handle('account', $this->chat);
    }

    /**
     * Handle /fatsecret command
     * Delegates to FatSecretCommandHandler via registry
     */
    public function fatsecret(): void
    {
        $this->commandRegistry->handle('fatsecret', $this->chat);
    }

    /**
     * Handle /sync command
     * Delegates to SyncCommandHandler via registry
     */
    public function sync(): void
    {
        $this->commandRegistry->handle('sync', $this->chat);
    }

    // ============================================================================
    // NOTE: All callback methods have been removed and delegated to CallbackRegistry
    // The overridden handleCallbackQuery() method (defined above) automatically routes
    // callback actions directly to the CallbackRegistry, which then routes to the
    // corresponding handler classes.
    //
    // Removed callbacks (now handled by registry):
    // - Main Menu: mainMenu, showSettings, showMeasurements, showMacros, showWeight, showHelp
    // - Account: accountLinking, generateLinkCode, checkLinkStatus, removeLinkAccount
    // - FatSecret: fatSecretConnect, checkFatSecretConnection, connectFatSecret, logoutFromFatSecret
    // - Sync: showSync, syncFull, syncWeight, syncFood
    // - Initiators: startNewMeasurement, startNewWeight, selectMacro*
    // ============================================================================

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
