<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\FatSecretException;
use Carbon\Carbon;

class FatSecretFacade
{
    public function __construct(public FatSecretServiceInterface $fatSecretService)
    {

    }

    /**
     * @return void
     * @throws FatSecretException
     */
    public function getRequestToken(): void
    {
        $this->fatSecretService->getRequestToken();
    }

    /**
     * @param OAuth1CallbackDto $auth1CallbackDto
     * @return void
     * @throws FatSecretException
     */
    public function getAccessToken(OAuth1CallbackDto $auth1CallbackDto): void
    {
        $this->fatSecretService->getAccessToken($auth1CallbackDto);
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return array
     * @throws FatSecretException
     */
    public function getMonthWeights(OAuthTokenDto $authTokenDTO, Carbon $date): array
    {
      return $this->fatSecretService->getMonthWeights($authTokenDTO, $date);
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return FoodEntryDto
     * @throws FatSecretException
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, Carbon $date): FoodEntryDto
    {
        return $this->fatSecretService->getFoodEntry($authTokenDTO, $date);
    }
}
