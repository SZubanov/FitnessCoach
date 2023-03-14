<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;

class FatSecretService
{
    public function __construct(
        private FatSecretAuth $fatSecretAuth,
        private FatSecretRepository $fatSecretRepository
    ) {

    }


    /**
     * @return void
     * @throws FatSecretException
     * @throws GuzzleException
     */
    public function getRequestToken(): void
    {
        try {
            $temporaryCredentials = $this->fatSecretAuth->getTemporaryCredentials();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new FatSecretException('Error while getting temporary credentials');
        }

        $this->fatSecretRepository->storeTemporaryCredentials($temporaryCredentials, Auth::id());
        $this->fatSecretAuth->authorize($temporaryCredentials);
    }

    /**
     * @param OAuth1CallbackDto $oAuth1CallbackDto
     * @return void
     * @throws FatSecretException
     * @throws GuzzleException
     */
    public function getAccessToken(OAuth1CallbackDto $oAuth1CallbackDto): void
    {
        $temporaryCredentials = $this->fatSecretRepository->getTemporaryCredentials($oAuth1CallbackDto->oauth_token);
        if ($temporaryCredentials === null) {
            throw new FatSecretException('Temporary credentials is expired');
        }

        try {
            [
                $userOAuthToken,
                $userOAuthSecret
            ] = $this->fatSecretAuth->getAccessToken($temporaryCredentials['temporaryCredentials'], $oAuth1CallbackDto);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new FatSecretException('Error while getting access token');
        }
        $this->fatSecretRepository->updateAccessToken($temporaryCredentials['userId'], $userOAuthToken, $userOAuthSecret);
    }
}
