<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Dto\Web\SuccessResponse;
use App\Exceptions\ServerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryMacrosStoreRequest;
use Illuminate\Http\JsonResponse;

class UserDiaryMacrosStoreController extends Controller
{
    /**
     * @throws ServerException
     */
    public function __invoke(
        DiaryMacrosStoreRequest $request,
        StoreUserDiaryMacrosInterface $storeUserDiaryMacros
    ): JsonResponse {

        try {
            $storeUserDiaryMacros($request->getData());
            $response = new SuccessResponse();
        } catch (\Exception $exception) {
            throw new ServerException();
        }

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
