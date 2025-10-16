<?php

namespace App\Telegram\Commands\Contracts;

use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Interface for Telegram command handlers
 *
 * Each command handler should implement this interface to be
 * registered with the TelegramCommandRegistry.
 */
interface TelegramCommandHandler
{
    /**
     * Handle the command execution
     *
     * @param TelegraphChat $chat The chat where the command was invoked
     * @return void
     */
    public function handle(TelegraphChat $chat): void;

    /**
     * Get the command name (e.g., 'start', 'help')
     *
     * @return string Command name without the leading slash
     */
    public function getCommandName(): string;
}