<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth1\Client\Credentials\CredentialsException;
use Log;

class FatSecretService
{
    public function __construct(
        private FatSecretAuth $fatSecretAuth,
        private FatSecretRepository $fatSecretRepository,
        private FatSecret $fatSecret,
        private Client $client
    ) {
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws CredentialsException
     */
    public function getRequestToken(): void
    {
        $temporaryCredentials = $this->fatSecretAuth->getTemporaryCredentials();
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

        [$userOAuthToken, $userOAuthSecret] = $this->fatSecretAuth
            ->getAccessToken($temporaryCredentials['temporaryCredentials'], $oAuth1CallbackDto);

        $this->fatSecretRepository
            ->updateAccessToken($temporaryCredentials['userId'], $userOAuthToken, $userOAuthSecret);
    }

    /**
     * @param int $date
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getMonthWeights(int $date)
    {
        $parameters = $this->fatSecret->buildRequestParameters(['date' => $date, 'method' => 'weights.get_month']);
        $parameters['oauth_signature'] = $this->fatSecret->signRequest($parameters);
        $response = $this->client->get(FatSecret::FATSECRET_URL . "?" . http_build_query($parameters));
        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
