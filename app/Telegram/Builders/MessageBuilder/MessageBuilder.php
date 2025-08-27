<?php

namespace App\Telegram\Builders\MessageBuilder;

class MessageBuilder implements MessageBuilderInterface
{
    private string $appUrl;

    public function __construct()
    {
        $this->appUrl = config('app.url');
    }

    public function createNewLinkAccountMessage(string $linkCode): string
    {
        return __('telegram.messages.account_link.new', [
            'code' => "`{$linkCode}`",
            'url' => $this->appUrl
        ]);
    }

    public function createExistingLinkAccountMessage(string $name, string $email): string
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

    public function createChooseDateMessage(): string
    {
        return __('telegram.messages.choose_date');
    }

    public function createCustomDateMessage(): string
    {
        return __('telegram.messages.custom_date');
    }

    public function createApiErrorHandlerMessage(): string
    {
        return __('telegram.messages.api_error');
    }

    public function createExceptionHandlerMessage(): string
    {
        return __('telegram.messages.exception_error');
    }
}
