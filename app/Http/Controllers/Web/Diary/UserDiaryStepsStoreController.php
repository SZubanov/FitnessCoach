<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryStepsInterface;
use App\Dto\Web\SuccessResponse;
use App\Exceptions\ServerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryStepsStoreRequest;
use Illuminate\Http\JsonResponse;

class UserDiaryStepsStoreController extends Controller
{
    /**
     * @throws ServerException
     */
    public function __invoke(
        DiaryStepsStoreRequest $request,
        StoreUserDiaryStepsInterface $storeUserDiarySteps
    ): JsonResponse {

        try {
            $storeUserDiarySteps($request->getData());
            $response = new SuccessResponse();
        } catch (\Exception $exception) {
            throw new ServerException();
        }

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
