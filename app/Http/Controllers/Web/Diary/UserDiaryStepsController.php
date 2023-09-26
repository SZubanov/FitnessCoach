<?php

namespace App\Http\Controllers\Web\Diary;

use App\Dto\Web\FormDto;
use App\Http\Controllers\Controller;

class UserDiaryStepsController extends Controller
{
    public function create()
    {
        $with['method'] = 'create';


        $response = FormDto::from([
                'action' => 'success',
                'html'   => view('diary.form-steps')->with($with)->render(),
                'title'  => __('diary.create.steps.title'),
                'button' => __('diary.create.button')
            ]
        );

        return response()->json($response);
    }
}
