<?php

namespace App\FatSecret;

use Exception;

class FatSecretException extends Exception
{
    private const DEFAULT_CODE = 400,
         DEFAULT_MESSAGE = 'FatSecret error';

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }

}
