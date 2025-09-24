<?php

namespace App\Telegram\Commands;

use App\Telegram\Constants\CommandConstants;
use App\Telegram\Utilities\CommandHelper;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class HelpCommand extends Command
{
    protected string $command = CommandConstants::HELP;

    protected ?string $description = CommandConstants::DESCRIPTIONS[CommandConstants::HELP];

    public function handle(Nutgram $bot): void
    {
        $helpText = "🆘 **Помощь по командам FitnessCoach**\n\n" .
                   CommandHelper::formatCommandsHelp() . "\n\n" .
                   "🔗 **Команды с параметрами:**\n" .
                   "• /" . CommandConstants::SYNC . " полная - Полная синхронизация с FatSecret";

        $bot->sendMessage($helpText);
    }
}
