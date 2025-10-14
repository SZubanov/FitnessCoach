<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Dto\WeightDto;
use App\FatSecret\Exceptions\FatSecretException;
use Carbon\Carbon;

interface FatSecretFacadeInterface
{
    /**
     * @return string
     * @throws FatSecretException
     */
    public function getRequestToken(): string;

    /**
     * @param OAuth1CallbackDto $auth1CallbackDto
     * @return void
     * @throws FatSecretException
     */
    public function getAccessToken(OAuth1CallbackDto $auth1CallbackDto): void;

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return WeightDto
     * @throws FatSecretException
     */
    public function getWeightByDate(OAuthTokenDto $authTokenDTO, Carbon $date): WeightDto;

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return FoodEntryDto
     * @throws FatSecretException
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, Carbon $date): FoodEntryDto;
}