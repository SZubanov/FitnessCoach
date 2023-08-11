<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\ResponseDecodeException;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * @return array
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     */
    public function getMonthWeights(OAuthTokenDto $authTokenDTO, Carbon $date): array;

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return Collection
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, Carbon $date): Collection;
}
