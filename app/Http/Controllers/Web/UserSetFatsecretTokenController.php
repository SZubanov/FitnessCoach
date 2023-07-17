<?php

namespace App\Http\Controllers\Web;

use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class UserSetFatSecretTokenController extends Controller
{
    /**
     * @param User $user
     * @param FatSecretFacade $fatSecretFacade
     * @return Application|Factory|View
     */
    public function __invoke(User $user, FatSecretFacade $fatSecretFacade): View|Factory|Application
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
