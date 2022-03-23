<?php
declare(strict_types=1);

namespace App\Contracts\Actions\Datatables;

use Spatie\LaravelData\DataCollection;

interface UserListsColumns
{
    public function __invoke(): DataCollection;
}
