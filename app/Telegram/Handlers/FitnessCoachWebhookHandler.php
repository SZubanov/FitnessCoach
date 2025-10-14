<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Telegram\Exceptions\UserNotFoundException;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Stringable;
use Throwable;

class FitnessCoachWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly TelegramUserService $telegramUserService,
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
}
