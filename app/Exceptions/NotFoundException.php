<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class NotFoundException extends Exception
{
    private const DEFAULT_MESSAGE = 'Record not found';
    private const DEFAULT_CODE = 404;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()
            ->json(['code' => self::DEFAULT_CODE, 'error' => __('response.error.404')])
            ->setStatusCode(self::DEFAULT_CODE);
    }
}
