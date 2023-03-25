<?php

namespace App\FatSecret\Exceptions;

class ResponseDecodeException extends FatSecretException
{
    private const DEFAULT_MESSAGE = 'FatSecret: response json decode error';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
