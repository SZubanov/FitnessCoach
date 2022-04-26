<?php

namespace App\Http\Controllers\Web\Admin\Users;

use App\Contracts\Actions\Users\GetListRolesInterface;
use App\Dto\Web\FormDto;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserCreateFormController extends Controller
{
    public function __invoke(GetListRolesInterface $getListRoles): JsonResponse
    {
        $with['method'] = 'create';
        $with['roles'] =  $getListRoles();

        $response = FormDto::from([
                'action' => 'success',
                'html'   => view('users.form')->with($with)->render(),
                'title'  => __('users.create.title'),
                'button' => __('users.create.button'),
            ]
        );

        return response()->json($response);
    }
}
