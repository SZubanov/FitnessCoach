<?php

namespace App\Http\Controllers\Web\Users;

use App\Contracts\Actions\Users\GetListRolesInterface;
use App\Dto\Web\FormDto;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserUpdateFormController extends Controller
{
    public function __invoke(User $user, GetListRolesInterface $getListRoles): JsonResponse
    {
        $with['method'] = 'update';
        $with['user'] = $user->load('roles');
        $with['roles'] =  $getListRoles();

        $response = FormDto::from([
                'action' => 'success',
                'html'   => view('users.form')->with($with)->render(),
                'title'  => __('users.update.title'),
                'button' => __('users.update.button'),
            ]
        );


        return response()->json($response);
    }
}
