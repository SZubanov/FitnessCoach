<?php

namespace App\Http\Controllers\Web;

use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserSetFatsecretTokenController extends Controller
{
    /**
     * @param User $user
     * @param FatSecretFacade $fatSecretFacade
     * @return RedirectResponse|View
     */
    public function __invoke(User $user, FatSecretFacade $fatSecretFacade)
    {
        try {
            $authUrl = $fatSecretFacade->getRequestToken();
        } catch (\Exception $e) {
            return view('settings')
                ->withErrors(['error' => $e->getMessage()])
                ->with(['user' => $user]);
        }
        return redirect()->away($authUrl);
    }
}
