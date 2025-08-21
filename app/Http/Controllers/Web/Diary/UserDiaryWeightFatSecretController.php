<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\GetUserDiaryWeightWithFatSecretInterface;
use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Dto\Web\SuccessResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryDateRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class UserDiaryWeightFatSecretController extends Controller
{
    public function __invoke(
        DiaryDateRequest $request,
        GetUserDiaryWeightWithFatSecretInterface $getUserDiaryWeightWithFatSecret,
        GetCurrentUserInterface $getCurrentUser
    ):  JsonResponse
    {
        $getUserDiaryWeightWithFatSecret(Carbon::parse($request->validated()['date']), $getCurrentUser());
        $response = new SuccessResponse();

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
