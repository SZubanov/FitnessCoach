<?php

namespace App\Http\Controllers\Web\Users;

use App\Dto\Web\FormDto;
use App\Dto\Web\UserFormDto;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserUpdateFormController extends Controller
{
    public function __invoke(User $user): JsonResponse
    {
        $with['method'] = 'update';
        $with['user'] = $user;

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
