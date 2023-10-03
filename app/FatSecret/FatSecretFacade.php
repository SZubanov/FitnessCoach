<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Dto\WeightDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\ResponseDecodeException;
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
     * @return WeightDto
     * @throws FatSecretException
     */
    public function getWeightByDate(OAuthTokenDto $authTokenDTO, Carbon $date): WeightDto
    {
      return $this->fatSecretService->getWeightByDate($authTokenDTO, $date);
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return FoodEntryDto
     * @throws FatSecretException
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, Carbon $date): FoodEntryDto
    {
        return $this->fatSecretService->getFoodEntryByDate($authTokenDTO, $date);
    }
}
