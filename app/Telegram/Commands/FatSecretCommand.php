<?php

namespace App\Telegram\Commands;

use App\Telegram\Constants\CommandConstants;
use App\Telegram\Menus\FatSecretConnectionMenu;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class FatSecretCommand extends Command
{
    protected string $command = CommandConstants::FATSECRET;

    protected ?string $description = CommandConstants::DESCRIPTIONS[CommandConstants::FATSECRET];

    public function handle(Nutgram $bot): void
    {
        FatSecretConnectionMenu::begin($bot);
    }
}
