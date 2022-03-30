<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class DatatableSuccessResponse extends Data
{
    public string $action;
    public string $success;

    public function __construct()
    {
        $this->action =  'reload_table';
        $this->success = __('datatables.store.success');
    }
}
