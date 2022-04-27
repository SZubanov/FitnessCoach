<?php

namespace App\Http\Controllers\Web;

use App\FatSecret\FatSecret;
use App\Http\Controllers\Controller;
use App\Models\User;

class UserSetFatsecretTokenController extends Controller
{
    public function __invoke(User $user)
    {
        $fs = new FatSecret([
            'identifier' => config('services.fatsecret.key'),
            'secret' => config('services.fatsecret.secret'),
            'callback_uri' => "oob",
        ]);
        $temporaryCredentials = $fs->getTemporaryCredentials();
         dd($temporaryCredentials);
    }
}
