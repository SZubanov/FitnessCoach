<?php

namespace App\Exceptions;

class FatSecretLogoutException  extends \RuntimeException
{
    private const DEFAULT_MESSAGE = 'FatSecret revoke token error';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
