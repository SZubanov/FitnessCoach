<?php

namespace App\Http\Controllers\Web;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\OAuth1CallbackRequest;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Spatie\LaravelData\Exceptions\InvalidDataClass;

class UserSetFatSecretTokenCallbackController extends Controller
{
    /**
     * @param OAuth1CallbackRequest $request
     * @param FatSecretFacade $fatSecretFacade
     * @return Application|Factory|View|RedirectResponse|Redirector
     * @throws InvalidDataClass
     */
    public function __invoke(OAuth1CallbackRequest $request, FatSecretFacade $fatSecretFacade): View|Factory|Redirector|RedirectResponse|Application
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
