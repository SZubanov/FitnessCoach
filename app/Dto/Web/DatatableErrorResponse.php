<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class DatatableErrorResponse extends Data
{
    public string $error;

    public function __construct()
    {
        $this->error =  __('datatables.store.error');
    }
}
