<?php

namespace App\Telegram\Builders\MessageBuilder;

interface MessageBuilderInterface
{
    public function createNewLinkAccountMessage(string $linkCode): string;

    public function createExistingLinkAccountMessage(string $name, string $email): string;

    public function createWelcomeMessage(): string;

    public function createHelpMessage(): string;

    public function createChooseDateMessage(): string;

    public function createCustomDateMessage(): string;

    public function createApiErrorHandlerMessage(): string;

    public function createExceptionHandlerMessage(): string;
}
