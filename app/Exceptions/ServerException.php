<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

class ServerException extends \Exception
{
    private const DEFAULT_MESSAGE = 'Record not found';
    private const DEFAULT_CODE = 500;

    public function __construct(string $message = self::DEFAULT_MESSAGE, $code = self::DEFAULT_CODE)
    {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()
            ->json(['code' => self::DEFAULT_CODE, 'error' => __('response.error.500')])
            ->setStatusCode(self::DEFAULT_CODE);
    }
}
