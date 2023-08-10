<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\DtoFactory;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\CredentialsException;
use Auth;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use JsonException;
use League\OAuth1\Client\Credentials\CredentialsException as LeagueCredentialsException;

class FatSecretService implements FatSecretServiceInterface
{
    public function __construct(
        private FatSecretAuth $fatSecretAuth,
        private FatSecretRepository $fatSecretRepository,
        private FatSecretClient $fatSecretClient,
        private DtoFactory $dtoFactory
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
     * @param OAuthTokenDto $authTokenDTO
     * @param int $date
     * @return array
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getMonthWeights(OAuthTokenDto $authTokenDTO, int $date): array
    {
        return $this->fatSecretClient->get(
            $authTokenDTO,
            [
                'date' => $date,
                'method' => FatSecret::GET_MONTH_WEIGHT_METHOD
            ]);
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param int $date
     * @return Collection
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, int $date):Collection
    {
        $foodEntry = $this->fatSecretClient->get(
            $authTokenDTO,
            [
                'date' => $date,
                'method' => FatSecret::GET_FOOD_ENTRY_METHOD
            ]);

        return $this->dtoFactory
            ->createFoodEntryDtoCollection($foodEntry['food_entries']['food_entry']);
    }
}
