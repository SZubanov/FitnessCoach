<?php

namespace App\Telegram\Commands;

use App\Telegram\Constants\CommandConstants;
use App\Telegram\Menus\AccountLinkingMenu;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class AccountCommand extends Command
{
    protected string $command = CommandConstants::ACCOUNT;

    protected ?string $description = CommandConstants::DESCRIPTIONS[CommandConstants::ACCOUNT];

    public function handle(Nutgram $bot): void
    {
        AccountLinkingMenu::begin($bot);
    }
}