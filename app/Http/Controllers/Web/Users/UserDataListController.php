<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Users\GetListUsers;
use App\Contracts\Actions\Datatables\ResponseElementsInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserDataListController extends Controller
{
    public function __invoke(GetListUsers $usersList, ResponseElementsInterface $elements): JsonResponse
    {
        return $elements($usersList());
    }
}
