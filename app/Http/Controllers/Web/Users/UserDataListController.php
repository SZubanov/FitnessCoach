<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Datatables\UserElements;
use App\Actions\Users\GetListUsers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserDataListController extends Controller
{
    public function __invoke(GetListUsers $usersList, UserElements $elements): JsonResponse
    {
        return $elements($usersList());
    }
}
