<?php

namespace App\Http\Controllers\Web;

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
    public function __invoke(User $user, UserUpdateRequest $request, UpdateUserInterface $updateUser)
    {
        /** @var UserUpdateDto $request */
        $update = $updateUser($user, $request->getData());
        if ($update) {
            return redirect()->back()->with(['success' => __('datatables.store.success')]);
        }

        return back()->withErrors(__('datatables.store.error'));

    }
}
