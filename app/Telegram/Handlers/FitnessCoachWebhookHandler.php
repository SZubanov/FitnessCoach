<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramFatSecretService;
use App\Telegram\Callbacks\CallbackRegistry;
use App\Telegram\Conversations\ConversationManager;
use App\Telegram\Exceptions\NoActiveConversationException;
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
        private readonly ConversationManager $conversationManager,
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
     * Delegates all conversation handling to ConversationManager (Phase 4).
     * The manager routes messages to appropriate conversation handlers based on type.
     *
     * Flow:
     * 1. Delegate to ConversationManager
     * 2. If no active conversation, show help message
     *
     * @param Stringable $text The incoming message text
     * @return void
     */
    protected function handleChatMessage(Stringable $text): void
    {
        try {
            $this->conversationManager->route($this->chat, $text);
        } catch (NoActiveConversationException $e) {
            $this->chat->html(
                "💬 Я понимаю только команды.\n\n" .
                "Используйте /help для списка доступных команд\n" .
                "или нажмите /start для главного меню."
            )->send();
        }
    }

    // ============================================================================
    // NOTE: All conversation handling methods have been migrated to Phase 4
    // conversation handlers and are now managed by ConversationManager.
    //
    // The handleChatMessage() method (defined above) delegates all conversation
    // routing to ConversationManager, which routes to specialized handler classes:
    // - MeasurementConversationHandler (app/Telegram/Conversations/)
    // - WeightConversationHandler
    // - MacroConversationHandler
    // - SyncConversationHandler
    // ============================================================================

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
