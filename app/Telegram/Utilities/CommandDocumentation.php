<?php

namespace App\Telegram\Utilities;

use App\Telegram\Constants\CommandConstants;

class CommandDocumentation
{
    /**
     * Handle unknown command with helpful suggestions
     */
    public static function handleUnknownCommand(string $unknownCommand): string
    {
        // Try to find similar commands
        $allCommands = array_keys(CommandConstants::DESCRIPTIONS);
        $suggestions = [];

        foreach ($allCommands as $command) {
            if (levenshtein(strtolower($unknownCommand), $command) <= 2) {
                $suggestions[] = "/{$command}";
            }
        }

        $message = "❓ **Неизвестная команда:** /{$unknownCommand}\n\n";

        if (!empty($suggestions)) {
            $message .= "💡 **Возможно, вы имели в виду:**\n" . implode(', ', $suggestions) . "\n\n";
        }

        $message .= "📋 Используйте /help для полного списка команд";

        return $message;
    }
}
