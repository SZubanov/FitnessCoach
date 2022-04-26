<?php

namespace App\Http\Controllers\Web\Admin\Users;

use App\Contracts\Actions\Users\StoreUserInterface;
use App\Dto\Web\DatatableErrorResponse;
use App\Dto\Web\DatatableSuccessResponse;
use App\Dto\Web\UserStoreDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Users\UserStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UserStoreController extends Controller
{
    public function __invoke(UserStoreRequest $request, StoreUserInterface $storeUser): JsonResponse|RedirectResponse
    {
        /** @var UserStoreDto $request */
        $store = $storeUser($request->getData());
        $response = new DatatableSuccessResponse();
        if (!$store) {
            $response = new DatatableErrorResponse();
        }


        return response()->json($response->toArray());
    }

}
