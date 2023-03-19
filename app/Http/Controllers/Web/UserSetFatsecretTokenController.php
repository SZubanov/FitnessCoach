<?php

namespace App\Http\Controllers\Web;

use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;

class UserSetFatSecretTokenController extends Controller
{
    /**
     * @throws GuzzleException
     */
    public function __invoke(User $user, FatSecretFacade $fatSecretFacade)
    {
        try {
            $fatSecretFacade->getRequestToken();
        } catch (\Exception $e) {
            return view('settings')
                ->withErrors(['error' => $e->getMessage()])
                ->with(['user' => $user]);
        }
        return view('settings')
            ->with(['user' => $user]);
    }
}
