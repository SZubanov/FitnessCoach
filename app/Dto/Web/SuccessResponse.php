<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class SuccessResponse extends Data
{
    public string $success;
    public int $code;

    public function __construct()
    {
        $this->success = __('datatables.store.success');
        $this->code = 200;
    }
}
