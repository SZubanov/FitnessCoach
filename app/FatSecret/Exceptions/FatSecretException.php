<?php

namespace App\FatSecret\Exceptions;

use Exception;

abstract class FatSecretException extends Exception
{
    private const    DEFAULT_MESSAGE = 'FatSecret error';
    private const DEFAULT_CODE = 400;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }

}
