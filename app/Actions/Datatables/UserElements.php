<?php
declare(strict_types=1);

namespace App\Actions\Datatables;

use App\Contracts\Actions\Datatables\ResponseElements;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;

class UserElements implements ResponseElements
{
    public function __invoke(Collection $queryResult): JsonResponse
    {
        return DataTables::of($queryResult)
            ->addColumn('action', 'users.actions')
            ->rawColumns(['action'])
            ->make(true);
    }
}
