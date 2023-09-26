<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Dto\Web\ErrorResponse;
use App\Dto\Web\SuccessResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryMacrosStoreRequest;
use Illuminate\Http\JsonResponse;

class UserDiaryMacrosStoreController extends Controller
{
    public function __invoke(
        DiaryMacrosStoreRequest $request,
        StoreUserDiaryMacrosInterface $storeUserDiaryMacros
    ): JsonResponse {

        try {
            $storeUserDiaryMacros($request->getData());
            $response = new SuccessResponse();
        } catch (\Exception $exception) {
            $response = new ErrorResponse($exception->getCode());
        }

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
