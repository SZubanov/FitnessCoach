<?php

namespace App\Http\Controllers\Web\Diary;

use App\Actions\Diary\StoreUserDiarySizes;
use App\Dto\Web\SuccessResponse;
use App\Exceptions\ServerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiarySizesStoreRequest;
use Illuminate\Http\JsonResponse;

class UserDiarySizesStoreController extends Controller
{
    /**
     * @throws ServerException
     */
    public function __invoke(
        DiarySizesStoreRequest $request,
        StoreUserDiarySizes $storeUserDiarySizes
    ): JsonResponse {

        try {
            $storeUserDiarySizes($request->getData());
            $response = new SuccessResponse();
        } catch (\Exception $exception) {
            throw new ServerException();
        }

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
