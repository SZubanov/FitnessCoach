<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\GetUserDiaryWeightWithFatSecretInterface;
use App\Dto\Web\SuccessResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryDateRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class UserDiaryWeightFatSecretController extends Controller
{
    public function __invoke(
        DiaryDateRequest $request,
        GetUserDiaryWeightWithFatSecretInterface $getUserDiaryWeightWithFatSecret
    ):  JsonResponse
    {
        $getUserDiaryWeightWithFatSecret(Carbon::parse($request->validated()['date']));
        $response = new SuccessResponse();

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
