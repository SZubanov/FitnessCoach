<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\OAuthTokenDTO;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\ResponseDecodeException;

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
     * @param OAuthTokenDTO $authTokenDTO
     * @param int $date
     * @return array
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     */
    public function getMonthWeights(OAuthTokenDTO $authTokenDTO, int $date): array;

    /**
     * @param OAuthTokenDTO $authTokenDTO
     * @param int $date
     * @return array
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     */
    public function getFoodEntry(OAuthTokenDTO $authTokenDTO, int $date): array;
}
