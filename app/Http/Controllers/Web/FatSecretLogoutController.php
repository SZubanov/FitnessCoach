<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Actions\Users\FatSecretLogoutInterface;
use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Http\Controllers\Controller;

class FatSecretLogoutController extends Controller
{
    public function __invoke(GetCurrentUserInterface $currentUser, FatSecretLogoutInterface $action)
    {
        $user = $currentUser();
        $action($user);

        return redirect('settings')->with(['user' => $user]);
    }
}
