<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Dto\WeightDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RecordNotFoundException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\ResponseDecodeException;
use Carbon\Carbon;

interface FatSecretServiceInterface
{

    /**
     * @return void
     * @throws RequestErrorException|Exceptions\CredentialsException
     */
    public function getRequestToken(): void;

    /**
     * @throws FatSecretException
     */
    public function getAccessToken(OAuth1CallbackDto $oAuth1CallbackDto): void;

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return WeightDto
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     * @throws RecordNotFoundException
     */
    public function getWeightByDate(OAuthTokenDto $authTokenDTO, Carbon $date): WeightDto;

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return FoodEntryDto
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     * @throws RecordNotFoundException
     */
    public function getFoodEntryByDate(OAuthTokenDto $authTokenDTO, Carbon $date): FoodEntryDto;
}
