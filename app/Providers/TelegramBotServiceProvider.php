<?php

namespace App\Providers;

use App\Telegram\Commands\AccountCommandHandler;
use App\Telegram\Commands\FatSecretCommandHandler;
use App\Telegram\Commands\HelpCommandHandler;
use App\Telegram\Commands\StartCommandHandler;
use App\Telegram\Commands\SyncCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramCommandRegistry;
use App\Telegram\Services\TelegramUserService;
use Illuminate\Support\ServiceProvider;

/**
 * Telegram Bot Service Provider
 *
 * Registers and configures all Telegram bot services, including:
 * - Command handlers
 * - Keyboard factory
 * - Command registry
 *
 * This provider bootstraps the Telegram bot infrastructure created
 * during the Phase 1 & Phase 2 refactoring.
 */
class TelegramBotServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     *
     * Binds singletons for:
     * - KeyboardFactory (shared across all handlers)
     * - TelegramCommandRegistry (central command router)
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
    }

    /**
     * Bootstrap any application services
     *
     * Registers all command handlers with the TelegramCommandRegistry.
     * This is where we wire up all the command handlers created in Phase 2.
     *
     * @return void
     */
    public function boot(): void
    {
        // Get singleton instances
        $registry = $this->app->make(TelegramCommandRegistry::class);
        $keyboardFactory = $this->app->make(KeyboardFactory::class);
        $userService = $this->app->make(TelegramUserService::class);

        // Register all command handlers
        $this->registerCommandHandlers($registry, $keyboardFactory, $userService);
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
}