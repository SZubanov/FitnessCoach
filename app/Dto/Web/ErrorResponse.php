<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class ErrorResponse extends Data
{
    public string $error;
    public int $code;

    public function __construct($code = 500)
    {
        $this->code = $code;
        $this->error =  __('datatables.store.error');
    }
}
