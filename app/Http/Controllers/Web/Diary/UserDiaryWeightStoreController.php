<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Contracts\Actions\Diary\StoreUserDiaryWeightInterface;
use App\Dto\Web\SuccessResponse;
use App\Exceptions\ServerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryMacrosStoreRequest;
use App\Http\Requests\Web\Diary\DiaryWeightStoreRequest;
use Illuminate\Http\JsonResponse;

class UserDiaryWeightStoreController extends Controller
{
    /**
     * @throws ServerException
     */
    public function __invoke(
        DiaryWeightStoreRequest $request,
        StoreUserDiaryWeightInterface $storeUserDiaryWeight
    ): JsonResponse {

        try {
            $storeUserDiaryWeight($request->getData());
            $response = new SuccessResponse();
        } catch (\Exception $exception) {
            throw new ServerException();
        }

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
