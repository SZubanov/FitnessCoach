<?php

namespace App\FatSecret\Exceptions;

class CredentialsException extends FatSecretException
{
    private const DEFAULT_MESSAGE = 'FatSecret: error get credentials';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
