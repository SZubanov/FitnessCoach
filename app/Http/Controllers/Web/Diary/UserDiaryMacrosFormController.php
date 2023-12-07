<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\GetDefaultWeightUnitUserInterface;
use App\Dto\Web\FormDto;
use App\Helpers\MetricSystem;
use App\Http\Controllers\Controller;

class UserDiaryMacrosFormController extends Controller
{
    public function __invoke(
        GetDefaultWeightUnitUserInterface $defaultWeightUnitUser
    )
    {
        $with['method'] = 'create';
        $with['unit'] = $defaultWeightUnitUser();

        $response = FormDto::from([
                'action' => 'success',
                'html'   => view('diary.form-macros')->with($with)->render(),
                'title'  => __('diary.create.macros.title'),
                'button' => __('diary.create.button')
            ]
        );

        return response()->json($response);
    }
}
