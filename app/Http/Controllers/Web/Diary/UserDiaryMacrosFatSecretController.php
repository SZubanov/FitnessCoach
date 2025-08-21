<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\GetUserDiaryMacrosWithFatSecretInterface;
use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Dto\Web\SuccessResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Diary\DiaryDateRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class UserDiaryMacrosFatSecretController extends Controller
{
    public function __invoke(
        DiaryDateRequest $request,
        GetUserDiaryMacrosWithFatSecretInterface $getUserDiaryMacrosWithFatSecret,
        GetCurrentUserInterface $getCurrentUser
    ): JsonResponse {

        $getUserDiaryMacrosWithFatSecret(Carbon::parse($request->validated()['date']), $getCurrentUser());
        $response = new SuccessResponse();

        return response()->json($response->toArray())->setStatusCode($response->code);
    }
}
