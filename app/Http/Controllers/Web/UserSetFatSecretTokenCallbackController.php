<?php

namespace App\Http\Controllers\Web;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\OAuth1CallbackRequest;

class UserSetFatSecretTokenCallbackController extends Controller
{
    public function __invoke(OAuth1CallbackRequest $request, FatSecretFacade $fatSecretFacade)
    {
        /** @var OAuth1CallbackDto $userAuthCredentials */
        $userAuthCredentials = $request->getData();
        $with['user'] = \Auth::user();

        try {
            $fatSecretFacade->getAccessToken($userAuthCredentials);
        } catch (\Exception $e) {
            return redirect('settings')
                ->withErrors(['error' => $e->getMessage()])
                ->with($with);
        }
        return view('settings')->with($with);
    }
}
