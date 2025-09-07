<?php

namespace App\Exceptions;


class CreateTelegramAccountLinkCodeException extends \RuntimeException
{
    private const    DEFAULT_MESSAGE = 'Redis. Create account link error';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
