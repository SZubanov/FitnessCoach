<?php

namespace App\FatSecret\Exceptions;

class RequestErrorException extends FatSecretException
{
    private const DEFAULT_MESSAGE = 'FatSecret: send request error';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
