<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class ErrorResponse extends Data
{
    public string $error;
    public int $code;

    public function __construct(string $error, int $code = 400)
    {
        $this->code = $code;
        $this->error =  $error;
    }
}
