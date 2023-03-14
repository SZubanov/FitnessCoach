<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use GuzzleHttp\Exception\GuzzleException;

class FatSecretFacade
{
    public function __construct(public FatSecretService $fatSecretService)
    {

    }


    /**
     * @return void
     * @throws FatSecretException
     * @throws GuzzleException
     */
    public function getRequestToken(): void
    {
        $this->fatSecretService->getRequestToken();
    }

    /**
     * @param OAuth1CallbackDto $auth1CallbackDto
     * @return void
     * @throws FatSecretException
     * @throws GuzzleException
     */
    public function getAccessToken(OAuth1CallbackDto $auth1CallbackDto): void
    {
        $this->fatSecretService->getAccessToken($auth1CallbackDto);
    }
}
