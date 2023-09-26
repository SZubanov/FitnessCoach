<?php

namespace App\Http\Controllers\Web\Diary;

use App\Contracts\Actions\Diary\GetDiaryUserInterface;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class UserDiaryPageController extends Controller
{
    public function __invoke(GetDiaryUserInterface $getDiaryUser): View
    {
        $response = [
            'user' => $getDiaryUser(\Auth::user()->getAuthIdentifier()),
        ];

        return view('diary.index')->with($response);
    }
}
