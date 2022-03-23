<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Datatables\UserListColumns;
use App\Actions\Users\GetListUsers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;

class UserListController extends Controller
{
    public function __invoke(GetListUsers $usersList, UserListColumns $columns)
    {
        if (request()->ajax()) {
            return $this->getDatatableElements($usersList);
        }

        $cols = $columns()->toArray();
        $with['columns'] = $cols;
        $with['jsonColumns'] = json_encode($cols, JSON_THROW_ON_ERROR);
        return view('users.list')->with($with);
    }

    public function getDatatableElements(GetListUsers $usersList): JsonResponse
    {
        $users = $usersList();

        return DataTables::of($users)
            ->addColumn('action', 'users.actions')
            ->rawColumns(['action'])
            ->make(true);
    }

}
