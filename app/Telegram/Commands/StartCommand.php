<?php

namespace App\Telegram\Commands;

use App\Telegram\Constants\CommandConstants;
use App\Telegram\Menus\MainMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Handlers\Type\Command;

class StartCommand extends Command
{
    protected string $command = CommandConstants::START;

    protected ?string $description = CommandConstants::DESCRIPTIONS[CommandConstants::START];

    public function handle(Nutgram $bot): void
    {
        $welcomeText = "🎯 **Добро пожаловать в FitnessCoach!**\n\n" .
                      "Я помогу вам отслеживать:\n" .
                      "⚖️ Вес и измерения тела\n" .
                      "🍎 Макронутриенты (КБЖУ)\n" .
                      "🔄 Синхронизацию с FatSecret\n\n" .
                      "Используйте меню ниже для начала работы:";

        $bot->sendMessage($welcomeText);

        MainMenu::begin($bot);
    }
}
