<?php

namespace App\Telegram\Exceptions;

class UserNotFoundException extends AbstractTelegramBotException
{
    private const DEFAULT_MESSAGE = 'Telegram. User not found by telegram_id';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
