<?php

namespace App\Telegram\Callbacks;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use DefStudio\Telegraph\Exceptions\TelegramWebhookException;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Callback Registry
 *
 * Central registry for callback action handlers with O(1) lookup.
 * Manages routing of Telegram callback queries to appropriate handlers.
 *
 * Pattern: Registry Pattern
 * Similar to TelegramCommandRegistry but for callback actions.
 */
class CallbackRegistry
{
    /**
     * Registered callback handlers
     *
     * @var array<string, CallbackHandler>
     */
    private array $handlers = [];

    /**
     * Register a callback handler
     *
     * Stores the handler by its callback name for O(1) lookup.
     * If a handler with the same name exists, it will be overwritten.
     *
     * @param CallbackHandler $handler The callback handler to register
     * @return void
     */
    public function register(CallbackHandler $handler): void
    {
        $this->handlers[$handler->getCallbackName()] = $handler;
    }

    /**
     * Register multiple callback handlers
     *
     * Convenient method for batch registration of handlers.
     *
     * @param iterable<CallbackHandler> $handlers Array or iterable of callback handlers
     * @return void
     */
    public function registerMany(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    /**
     * Handle a callback action
     *
     * Executes the registered handler for the given callback name.
     * Throws exception if callback is not registered.
     *
     * @param string $callbackName The callback action name (e.g., 'showSettings')
     * @param TelegraphChat $chat The Telegram chat instance
     * @param int|null $messageId The message ID to edit (optional)
     * @return void
     * @throws TelegramWebhookException
     */
    public function handle(string $callbackName, TelegraphChat $chat, ?int $messageId = null): void
    {
        if (!$this->has($callbackName)) {
            throw TelegramWebhookException::invalidAction($callbackName);
        }

        $this->handlers[$callbackName]->handle($chat, $messageId);
    }

    /**
     * Check if a callback is registered
     *
     * @param string $callbackName The callback action name to check
     * @return bool True if registered, false otherwise
     */
    public function has(string $callbackName): bool
    {
        return isset($this->handlers[$callbackName]);
    }

    /**
     * Get all registered callback names
     *
     * Useful for debugging and introspection.
     *
     * @return array<string> Array of registered callback action names
     */
    public function getRegisteredCallbacks(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Get all registered handlers
     *
     * Useful for advanced scenarios like bulk operations.
     *
     * @return array<string, CallbackHandler> Map of callback names to handlers
     */
    public function all(): array
    {
        return $this->handlers;
    }

    /**
     * Get count of registered handlers
     *
     * @return int Number of registered handlers
     */
    public function count(): int
    {
        return count($this->handlers);
    }
}
