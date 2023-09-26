<?php

namespace App\Http\Controllers\Web\Diary;

use App\Dto\Web\FormDto;
use App\Http\Controllers\Controller;

class UserDiaryMacrosFormController extends Controller
{
    public function __invoke()
    {
        $with['method'] = 'create';

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
