<?php

namespace App\Http\Controllers\Web\Admin\Users;

use App\Contracts\Actions\Users\DeleteUserInterface;
use App\Dto\Web\DatatableErrorResponse;
use App\Dto\Web\DatatableSuccessResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserDeleteController extends Controller
{
    public function __invoke(User $user, DeleteUserInterface $deleteUser): JsonResponse
    {
        $update = $deleteUser($user);
        $response = new DatatableSuccessResponse();
        if (!$update) {
            $response = new DatatableErrorResponse();
        }

        return response()->json($response->toArray());
    }
}
