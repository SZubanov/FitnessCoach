<?php

namespace App\Providers;

use App\Telegram\Callbacks\Account\AccountLinkingCallback;
use App\Telegram\Callbacks\Account\CheckLinkStatusCallback;
use App\Telegram\Callbacks\Account\GenerateLinkCodeCallback;
use App\Telegram\Callbacks\Account\RemoveLinkAccountCallback;
use App\Telegram\Callbacks\CallbackRegistry;
use App\Telegram\Callbacks\FatSecret\CheckFatSecretConnectionCallback;
use App\Telegram\Callbacks\FatSecret\ConnectFatSecretCallback;
use App\Telegram\Callbacks\FatSecret\FatSecretConnectCallback;
use App\Telegram\Callbacks\FatSecret\LogoutFatSecretCallback;
use App\Telegram\Callbacks\Macros\SelectMacroCarbsCallback;
use App\Telegram\Callbacks\Macros\SelectMacroCaloriesCallback;
use App\Telegram\Callbacks\Macros\SelectMacroFatsCallback;
use App\Telegram\Callbacks\Macros\SelectMacroProteinsCallback;
use App\Telegram\Callbacks\MainMenu\MainMenuCallback;
use App\Telegram\Callbacks\MainMenu\ShowHelpCallback;
use App\Telegram\Callbacks\MainMenu\ShowMacrosCallback;
use App\Telegram\Callbacks\MainMenu\ShowMeasurementsCallback;
use App\Telegram\Callbacks\MainMenu\ShowSettingsCallback;
use App\Telegram\Callbacks\MainMenu\ShowWeightCallback;
use App\Telegram\Callbacks\Measurements\StartNewMeasurementCallback;
use App\Telegram\Callbacks\Sync\ShowSyncCallback;
use App\Telegram\Callbacks\Sync\SyncFoodCallback;
use App\Telegram\Callbacks\Sync\SyncFullCallback;
use App\Telegram\Callbacks\Sync\SyncWeightCallback;
use App\Telegram\Callbacks\Weight\StartNewWeightCallback;
use App\Telegram\Commands\AccountCommandHandler;
use App\Telegram\Commands\FatSecretCommandHandler;
use App\Telegram\Commands\HelpCommandHandler;
use App\Telegram\Commands\StartCommandHandler;
use App\Telegram\Commands\SyncCommandHandler;
use App\Telegram\Conversations\ConversationManager;
use App\Telegram\Conversations\MacroConversationHandler;
use App\Telegram\Conversations\MeasurementConversationHandler;
use App\Telegram\Conversations\SyncConversationHandler;
use App\Telegram\Conversations\WeightConversationHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\MessageResponseBuilder;
use App\Telegram\Services\TelegramAccountService;
use App\Telegram\Services\TelegramCommandRegistry;
use App\Telegram\Services\TelegramUserService;
use Illuminate\Support\ServiceProvider;

/**
 * Telegram Bot Service Provider
 *
 * Registers and configures all Telegram bot services, including:
 * - Command handlers (Phase 2)
 * - Callback handlers (Phase 3)
 * - Conversation handlers (Phase 4)
 * - Keyboard factory
 * - Command registry
 * - Callback registry
 * - Conversation manager
 *
 * This provider bootstraps the Telegram bot infrastructure created
 * during the Phase 1, Phase 2, Phase 3, and Phase 4 refactoring.
 */
class TelegramBotServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     *
     * Binds singletons for:
     * - KeyboardFactory (shared across all handlers)
     * - TelegramCommandRegistry (central command router)
     * - CallbackRegistry (central callback router)
     * - ConversationManager (Phase 4 - conversation flow orchestration)
     *
     * @return void
     */
    public function register(): void
    {
        // Register KeyboardFactory as singleton
        // All handlers will share the same instance
        $this->app->singleton(KeyboardFactory::class);

        // Register TelegramCommandRegistry as singleton
        // Single registry for all command handlers
        $this->app->singleton(TelegramCommandRegistry::class);

        // Register CallbackRegistry as singleton
        // Single registry for all callback handlers
        $this->app->singleton(CallbackRegistry::class);

        // Register ConversationManager as singleton (Phase 4)
        // Manages conversation flow routing to specialized handlers
        $this->app->singleton(ConversationManager::class, function ($app) {
            $dateValidation = $app->make(DateValidationService::class);
            $keyboardFactory = $app->make(KeyboardFactory::class);
            $messageBuilder = $app->make(MessageResponseBuilder::class);

            return new ConversationManager(
                $app->make(ConversationStateService::class),
                $app->make(TelegramUserService::class),
                [
                    new MeasurementConversationHandler($dateValidation, $keyboardFactory, $messageBuilder),
                    new WeightConversationHandler($dateValidation, $keyboardFactory, $messageBuilder),
                    new MacroConversationHandler($dateValidation, $keyboardFactory, $messageBuilder),
                    new SyncConversationHandler($dateValidation, $keyboardFactory, $messageBuilder),
                ]
            );
        });

        // Tag conversation handlers for potential future use
        $this->app->tag([
            MeasurementConversationHandler::class,
            WeightConversationHandler::class,
            MacroConversationHandler::class,
            SyncConversationHandler::class,
        ], 'telegram.conversations');
    }

    /**
     * Bootstrap any application services
     *
     * Registers all command and callback handlers with their registries.
     * This is where we wire up all handlers created in Phase 2 and Phase 3.
     *
     * @return void
     */
    public function boot(): void
    {
        // Get singleton instances for commands
        $commandRegistry = $this->app->make(TelegramCommandRegistry::class);
        $keyboardFactory = $this->app->make(KeyboardFactory::class);
        $userService = $this->app->make(TelegramUserService::class);

        // Get additional services for callbacks
        $callbackRegistry = $this->app->make(CallbackRegistry::class);
        $accountService = $this->app->make(TelegramAccountService::class);
        $conversationState = $this->app->make(ConversationStateService::class);
        $dateValidation = $this->app->make(DateValidationService::class);

        // Register all command handlers (Phase 2)
        $this->registerCommandHandlers($commandRegistry, $keyboardFactory, $userService);

        // Register all callback handlers (Phase 3)
        $this->registerCallbackHandlers(
            $callbackRegistry,
            $keyboardFactory,
            $userService,
            $accountService,
            $conversationState,
            $dateValidation
        );
    }

    /**
     * Register all command handlers with the registry
     *
     * Each handler is instantiated with its dependencies and registered
     * with the command registry for routing.
     *
     * @param TelegramCommandRegistry $registry Command registry instance
     * @param KeyboardFactory $keyboardFactory Keyboard factory instance
     * @param TelegramUserService $userService User service instance
     * @return void
     */
    private function registerCommandHandlers(
        TelegramCommandRegistry $registry,
        KeyboardFactory $keyboardFactory,
        TelegramUserService $userService
    ): void {
        // Register /start command
        $registry->register(
            new StartCommandHandler($keyboardFactory)
        );

        // Register /help command
        $registry->register(
            new HelpCommandHandler($keyboardFactory)
        );

        // Register /account command
        $registry->register(
            new AccountCommandHandler($keyboardFactory)
        );

        // Register /fatsecret command
        $registry->register(
            new FatSecretCommandHandler($keyboardFactory)
        );

        // Register /sync command (requires FatSecret auth)
        $registry->register(
            new SyncCommandHandler($keyboardFactory, $userService)
        );
    }

    /**
     * Register all callback handlers with the registry
     *
     * Each handler is instantiated with its dependencies and registered
     * with the callback registry for routing.
     *
     * @param CallbackRegistry $registry Callback registry instance
     * @param KeyboardFactory $keyboardFactory Keyboard factory instance
     * @param TelegramUserService $userService User service instance
     * @param TelegramAccountService $accountService Account service instance
     * @param ConversationStateService $conversationState Conversation state service
     * @param DateValidationService $dateValidation Date validation service
     * @return void
     */
    private function registerCallbackHandlers(
        CallbackRegistry $registry,
        KeyboardFactory $keyboardFactory,
        TelegramUserService $userService,
        TelegramAccountService $accountService,
        ConversationStateService $conversationState,
        DateValidationService $dateValidation
    ): void {
        // ============================================================================
        // MAIN MENU CALLBACKS (6 handlers)
        // ============================================================================

        $registry->register(new MainMenuCallback($keyboardFactory));
        $registry->register(new ShowSettingsCallback($keyboardFactory));
        $registry->register(new ShowMeasurementsCallback($keyboardFactory, $userService));
        $registry->register(new ShowMacrosCallback($keyboardFactory, $userService));
        $registry->register(new ShowWeightCallback($keyboardFactory, $userService));
        $registry->register(new ShowHelpCallback($keyboardFactory));

        // ============================================================================
        // ACCOUNT CALLBACKS (4 handlers)
        // ============================================================================

        $registry->register(new AccountLinkingCallback($keyboardFactory));
        $registry->register(new GenerateLinkCodeCallback($accountService, $keyboardFactory));
        $registry->register(new CheckLinkStatusCallback($accountService, $keyboardFactory));
        $registry->register(new RemoveLinkAccountCallback($accountService, $keyboardFactory));

        // ============================================================================
        // FATSECRET CALLBACKS (4 handlers)
        // ============================================================================

        $registry->register(new FatSecretConnectCallback($keyboardFactory));
        $registry->register(new CheckFatSecretConnectionCallback($userService, $keyboardFactory));
        $registry->register(new ConnectFatSecretCallback($userService, $keyboardFactory));
        $registry->register(new LogoutFatSecretCallback($userService, $keyboardFactory));

        // ============================================================================
        // SYNC CALLBACKS (4 handlers)
        // ============================================================================

        $registry->register(new ShowSyncCallback($keyboardFactory, $userService));
        $registry->register(new SyncFullCallback($conversationState, $dateValidation));
        $registry->register(new SyncWeightCallback($conversationState, $dateValidation));
        $registry->register(new SyncFoodCallback($conversationState, $dateValidation));

        // ============================================================================
        // MEASUREMENT/WEIGHT/MACRO INITIATORS (6 handlers)
        // ============================================================================

        $registry->register(new StartNewMeasurementCallback(
            $conversationState,
            $dateValidation,
            $userService,
            $keyboardFactory
        ));

        $registry->register(new StartNewWeightCallback(
            $conversationState,
            $dateValidation,
            $userService,
            $keyboardFactory
        ));

        $registry->register(new SelectMacroCaloriesCallback(
            $conversationState,
            $dateValidation,
            $userService,
            $keyboardFactory
        ));

        $registry->register(new SelectMacroProteinsCallback(
            $conversationState,
            $dateValidation,
            $userService,
            $keyboardFactory
        ));

        $registry->register(new SelectMacroFatsCallback(
            $conversationState,
            $dateValidation,
            $userService,
            $keyboardFactory
        ));

        $registry->register(new SelectMacroCarbsCallback(
            $conversationState,
            $dateValidation,
            $userService,
            $keyboardFactory
        ));
    }
}