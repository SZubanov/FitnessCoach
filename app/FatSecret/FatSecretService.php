<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\CredentialsException;
use Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth1\Client\Credentials\CredentialsException as LeagueCredentialsException;

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
     * @throws LeagueCredentialsException
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
     * @throws GuzzleException|CredentialsException
     */
    public function getAccessToken(OAuth1CallbackDto $oAuth1CallbackDto): void
    {
        $temporaryCredentials = $this->fatSecretRepository->getTemporaryCredentials($oAuth1CallbackDto->oauth_token);
        if ($temporaryCredentials === null) {
            throw new CredentialsException('Temporary credentials is expired');
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
    public function getMonthWeights(int $date): array
    {
        $parameters = $this->fatSecret->buildRequestParameters(
            [
                'date' => $date,
                'method' => FatSecret::GET_MONTH_WEIGHT_METHOD
            ]);
        $parameters['oauth_signature'] = $this->fatSecret->signRequest($parameters);
        $response = $this->client->get(FatSecret::FATSECRET_URL . "?" . http_build_query($parameters));
        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param int $date
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getFoodEntry(int $date): array
    {
        $parameters = $this->fatSecret->buildRequestParameters([
            'date' => $date,
            'method' => FatSecret::GET_FOOD_ENTRY_METHOD
        ]);
        $parameters['oauth_signature'] = $this->fatSecret->signRequest($parameters);
        $response = $this->client->get(FatSecret::FATSECRET_URL . "?" . http_build_query($parameters));
        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
