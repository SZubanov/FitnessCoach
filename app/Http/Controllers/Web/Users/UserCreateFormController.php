<?php

namespace App\Http\Controllers\Web\Users;

use App\Dto\Web\FormDto;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserCreateFormController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $with['method'] = 'create';

        $response = FormDto::from([
                'action' => 'success',
                'html'   => view('users.form')->with($with)->render(),
                'title'  => __('users.create.title'),
                'button' => __('users.update.button'),
            ]
        );

        return response()->json($response);
    }
}
