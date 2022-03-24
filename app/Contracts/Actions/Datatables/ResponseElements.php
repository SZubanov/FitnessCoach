<?php
declare(strict_types=1);


namespace App\Contracts\Actions\Datatables;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

interface ResponseElements
{
    public function __invoke(Collection $queryResult): JsonResponse;
}
