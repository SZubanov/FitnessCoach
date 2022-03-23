<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class DatatableColumnDto extends Data
{
    public function __construct(
        public string $data,
        public string $column_name,
        public string $name,
        public ?bool $sortable,
        public ?bool $searchable,
        public ?string $width
    )
    {

    }
}
