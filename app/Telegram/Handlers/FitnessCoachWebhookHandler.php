<?php

namespace App\Telegram\Handlers;

use App\Telegram\Callbacks\CallbackRegistry;
use App\Telegram\Conversations\ConversationManager;
use App\Telegram\Exceptions\NoActiveConversationException;
use App\Telegram\Exceptions\UserNotFoundException;
use App\Telegram\Services\TelegramCommandRegistry;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Stringable;
use Throwable;

/**
 * FitnessCoachWebhookHandler - Main Telegram Bot Webhook Entry Point
 *
 * This handler acts as a thin orchestration layer that delegates all business logic
 * to specialized components using modern design patterns:
 *
 * Architecture:
 * - Commands: Delegated to TelegramCommandRegistry → Command Handlers
 * - Callbacks: Delegated to CallbackRegistry → Callback Handlers
 * - Conversations: Delegated to ConversationManager → Conversation Handlers
 *
 * Design Patterns Used:
 * - Command Pattern: Command handlers with registry
 * - Strategy Pattern: Conversation handlers with manager
 * - Registry Pattern: O(1) lookup for commands and callbacks
 *
 * Responsibilities:
 * 1. Override Telegraph's callback routing for direct registry delegation
 * 2. Route chat messages to ConversationManager
 * 3. Delegate commands to CommandRegistry
 * 4. Centralized error handling
 *
 * This handler was reduced from 1,406 lines (monolithic) to ~160 lines (orchestration)
 * through systematic refactoring across Phases 1-5 (Oct 2025).
 *
 * @see TelegramCommandRegistry For command routing
 * @see CallbackRegistry For callback routing
 * @see ConversationManager For conversation flow management
 */
class FitnessCoachWebhookHandler extends WebhookHandler
{
    /**
     * Construct handler with all dependencies injected
     *
     * All business logic is delegated to these services:
     * - commandRegistry: Routes commands to handlers
     * - callbackRegistry: Routes callbacks to handlers
     * - conversationManager: Routes messages to conversation handlers
     */
    public function __construct(
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
     * Benefits:
     * - Direct delegation (no magic methods)
     * - O(1) lookup via registry
     * - Type-safe handler classes
     * - Crystal clear call path
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

        // Delegate directly to CallbackRegistry
        $this->callbackRegistry->handle($action, $this->chat, $this->messageId);
    }

    /**
     * Handle incoming chat messages (non-command text)
     *
     * Delegates all conversation handling to ConversationManager.
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

    /**
     * Centralized error handling for all webhook exceptions
     *
     * Logs errors with context and sends user-friendly error messages in Russian.
     * Handles specific exception types with appropriate responses.
     *
     * @param Throwable $throwable The exception that occurred
     * @return void
     */
    protected function onFailure(Throwable $throwable): void
    {
        // Log the error with full context for debugging
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
                ->keyboard(Keyboard::make()->buttons([
                    Button::make('🔗 Привязать аккаунт')->action('accountLinking'),
                    Button::make('🏠 Главное меню')->action('mainMenu'),
                ]))
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
}