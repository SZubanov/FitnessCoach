<?php

namespace App\Telegram\Services;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Exceptions\UnknownCommandException;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Telegram Command Registry
 *
 * Central registry for all Telegram command handlers.
 * Implements the Command pattern and acts as a factory for command execution.
 */
class TelegramCommandRegistry
{
    /**
     * Registered command handlers
     *
     * @var array<string, TelegramCommandHandler>
     */
    private array $handlers = [];

    /**
     * Register a command handler
     *
     * @param TelegramCommandHandler $handler Command handler instance
     * @return void
     */
    public function register(TelegramCommandHandler $handler): void
    {
        $commandName = $handler->getCommandName();
        $this->handlers[$commandName] = $handler;
    }

    /**
     * Register multiple command handlers at once
     *
     * @param iterable<TelegramCommandHandler> $handlers Collection of handlers
     * @return void
     */
    public function registerMany(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    /**
     * Handle a command by delegating to the appropriate handler
     *
     * @param string $command Command name (without leading slash)
     * @param TelegraphChat $chat Telegram chat where command was invoked
     * @return void
     * @throws UnknownCommandException If no handler is registered for the command
     */
    public function handle(string $command, TelegraphChat $chat): void
    {
        if (!$this->has($command)) {
            throw UnknownCommandException::forCommand($command);
        }

        $this->handlers[$command]->handle($chat);
    }

    /**
     * Check if a command handler is registered
     *
     * @param string $command Command name to check
     * @return bool True if handler is registered
     */
    public function has(string $command): bool
    {
        return isset($this->handlers[$command]);
    }

    /**
     * Get a command handler by name
     *
     * @param string $command Command name
     * @return TelegramCommandHandler|null Handler instance or null if not found
     */
    public function get(string $command): ?TelegramCommandHandler
    {
        return $this->handlers[$command] ?? null;
    }

    /**
     * Get all registered command names
     *
     * @return array<string> Array of command names
     */
    public function getRegisteredCommands(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Get count of registered commands
     *
     * @return int Number of registered commands
     */
    public function count(): int
    {
        return count($this->handlers);
    }

    /**
     * Clear all registered handlers
     *
     * Useful for testing
     *
     * @return void
     */
    public function clear(): void
    {
        $this->handlers = [];
    }
}