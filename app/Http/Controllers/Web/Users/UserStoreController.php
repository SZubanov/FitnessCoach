<?php

namespace App\Http\Controllers\Web\Users;

use App\Actions\Users\StoreUser;
use App\Dto\Web\DatatableErrorResponse;
use App\Dto\Web\DatatableSuccessResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Users\UserStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UserStoreController extends Controller
{
    public function __invoke(UserStoreRequest $request, StoreUser $storeUser): JsonResponse|RedirectResponse
    {
        $store = $storeUser($request->getData());
        $response = new DatatableSuccessResponse();
        if (!$store) {
            $response = new DatatableErrorResponse();
        }


        return response()->json($response->toArray());
    }

}
