<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    public function __invoke(GetCurrentUserInterface $currentUser)
    {
        $with['user'] = $currentUser();
        return view('settings')->with($with);
    }
}
