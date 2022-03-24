<?php

namespace App\Http\Controllers\Web\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserCreateFormController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $title = __('users.create.title');
        $with['method'] = 'create';

        return response()->json([
            'action'    => 'success',
            'html'      => view('users.form')->with($with)->render(),
            'title'     => $title,
            'button'    => __('users.button'),
        ]);
    }
}
