<?php

namespace App\Http\Controllers\Web\Admin\Users;

use App\Contracts\Actions\Users\UpdateUserInterface;
use App\Dto\Web\DatatableErrorResponse;
use App\Dto\Web\DatatableSuccessResponse;
use App\Dto\Web\UserUpdateDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Users\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserUpdateController extends Controller
{
    public function __invoke(User $user, UserUpdateRequest $request, UpdateUserInterface $updateUser): JsonResponse
    {
        /** @var UserUpdateDto $request */
        $update = $updateUser($user, $request->getData());
        $response = new DatatableSuccessResponse();
        if (!$update) {
            $response = new DatatableErrorResponse();
        }


        return response()->json($response->toArray());

    }
}
