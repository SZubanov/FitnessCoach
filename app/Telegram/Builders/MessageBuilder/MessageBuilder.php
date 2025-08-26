<?php

namespace App\Telegram\Builders\MessageBuilder;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

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
}
