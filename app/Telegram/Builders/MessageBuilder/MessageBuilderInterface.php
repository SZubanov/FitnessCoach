<?php

namespace App\Telegram\Builders\MessageBuilder;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

interface MessageBuilderInterface
{
    public function createMessageNewLinkAccount(string $linkCode): string;

    public function createMessageExistingLinkAccount(string $name, string $email): string;
}
