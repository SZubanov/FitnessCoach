<?php

namespace App\Telegram\Exceptions;

class UnlinkTelegramAccountException extends AbstractTelegramBotException
{
    private const DEFAULT_MESSAGE = 'Telegram. Remove telegram account error';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
