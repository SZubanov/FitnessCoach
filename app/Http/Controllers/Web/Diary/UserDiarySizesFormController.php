<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Users\GetDefaultSizeUnitUserInterface;
use App\Dto\Web\FormDto;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserDiarySizesFormController extends Controller
{
    public function __invoke(GetDefaultSizeUnitUserInterface $defaultSizeUnitUser): JsonResponse
    {
        $with['method'] = 'create';
        $with['unit'] = $defaultSizeUnitUser();

        $response = FormDto::from([
                'action' => 'success',
                'html'   => view('diary.form-sizes')->with($with)->render(),
                'title'  => __('diary.create.sizes.title'),
                'button' => __('diary.create.button')
            ]
        );

        return response()->json($response);
    }
}
