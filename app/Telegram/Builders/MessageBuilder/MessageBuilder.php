<?php

namespace App\Telegram\Builders\MessageBuilder;

class MessageBuilder implements MessageBuilderInterface
{
    private string $appUrl;

    public function __construct()
    {
        $this->appUrl = config('app.url');
    }

    public function createMessageNewLinkAccount(string $linkCode): string
    {
        return __('telegram.messages.account_link.new', [
            'code' => "`{$linkCode}`",
            'url' => $this->appUrl
        ]);
    }

    public function createMessageExistingLinkAccount(string $name, string $email): string
    {
        return __('telegram.messages.account_link.exist', [
            'name' => $name,
            'email' => $email
        ]);
    }

    public function createWelcomeMessage(): string
    {
        return __('telegram.messages.welcome');
    }

    public function createHelpMessage(): string
    {
        return __('telegram.messages.help');
    }
}
