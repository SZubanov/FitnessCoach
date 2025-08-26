<?php

namespace App\Telegram\Builders\MessageBuilder;

interface MessageBuilderInterface
{
    public function createMessageNewLinkAccount(string $linkCode): string;

    public function createMessageExistingLinkAccount(string $name, string $email): string;

    public function createWelcomeMessage(): string;

    public function createHelpMessage(): string;
}
