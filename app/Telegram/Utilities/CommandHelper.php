<?php

namespace App\Telegram\Utilities;

use App\Telegram\Constants\CommandConstants;
use SergiX44\Nutgram\Nutgram;

class CommandHelper
{
    /**
     * Parse command parameters from the message text
     */
    public static function parseCommandParameters(Nutgram $bot): array
    {
        $messageText = $bot->message()?->text ?? '';
        $parts = explode(' ', $messageText);
        
        // Remove the command part (/command)
        array_shift($parts);
        
        return array_filter($parts);
    }

    /**
     * Get command parameter if valid for the command type
     */
    public static function getValidParameter(string $command, array $parameters): ?string
    {
        if (empty($parameters)) {
            return null;
        }

        $firstParam = $parameters[0];

        return match($command) {
            CommandConstants::SYNC => in_array($firstParam, CommandConstants::SYNC_PARAMS) ? $firstParam : null,
            default => null
        };
    }

    /**
     * Send command usage instructions
     */
    public static function sendUsageInstructions(Nutgram $bot, string $command): void
    {
        $message = match($command) {
            CommandConstants::SYNC => "⚠️ **Неверный параметр**\n\nИспользование: `/sync [тип]`\n\nДоступные типы: " . implode(', ', CommandConstants::SYNC_PARAMS),
            default => "⚠️ **Неверные параметры команды**"
        };

        $bot->sendMessage($message);
    }

    /**
     * Get all available commands with descriptions
     */
    public static function getCommandsList(): array
    {
        return CommandConstants::DESCRIPTIONS;
    }

    /**
     * Format commands list for help message
     */
    public static function formatCommandsHelp(): string
    {
        $commands = [];
        
        $commands[] = "📋 **Основные команды:**";
        $commands[] = "/" . CommandConstants::START . " - " . CommandConstants::DESCRIPTIONS[CommandConstants::START];
        $commands[] = "/" . CommandConstants::HELP . " - " . CommandConstants::DESCRIPTIONS[CommandConstants::HELP];

        $commands[] = "\n🚀 **Быстрые команды:**";
        $commands[] = "/" . CommandConstants::SYNC . " [тип] - " . CommandConstants::DESCRIPTIONS[CommandConstants::SYNC];

        $commands[] = "\n⚙️ **Управление:**";
        $commands[] = "/" . CommandConstants::SETTINGS . " - " . CommandConstants::DESCRIPTIONS[CommandConstants::SETTINGS];
        $commands[] = "/" . CommandConstants::ACCOUNT . " - " . CommandConstants::DESCRIPTIONS[CommandConstants::ACCOUNT];
        $commands[] = "/" . CommandConstants::FATSECRET . " - " . CommandConstants::DESCRIPTIONS[CommandConstants::FATSECRET];
        $commands[] = "/" . CommandConstants::STATUS . " - " . CommandConstants::DESCRIPTIONS[CommandConstants::STATUS];
        
        return implode("\n", $commands);
    }
}