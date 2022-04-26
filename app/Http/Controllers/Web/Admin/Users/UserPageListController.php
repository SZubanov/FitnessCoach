<?php

namespace App\Http\Controllers\Web\Admin\Users;

use App\Contracts\Actions\Datatables\UserListsColumnInterface;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use JsonException;

class UserPageListController extends Controller
{
    /**
     * @throws JsonException
     */
    public function __invoke(UserListsColumnInterface $columns): View
    {
        $cols = $columns()->toArray();
        $with = [
            'columns'     => $cols,
            'jsonColumns' => json_encode($cols, JSON_THROW_ON_ERROR)
        ];
        return view('users.list')->with($with);
    }
}
