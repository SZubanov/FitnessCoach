<?php

namespace App\FatSecret\Exceptions;

class RecordNotFoundException extends FatSecretException
{
    private const DEFAULT_MESSAGE = 'FatSecret: record not found';
    private const DEFAULT_CODE = 404;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }
}
