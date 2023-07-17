<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\OAuthTokenDTO;
use App\FatSecret\Exceptions\FatSecretException;

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
     * @param OAuthTokenDTO $authTokenDTO
     * @param int $date
     * @return array
     * @throws FatSecretException
     */
    public function getMonthWeights(OAuthTokenDTO $authTokenDTO, int $date): array
    {
      return $this->fatSecretService->getMonthWeights($authTokenDTO, $date);
    }

    /**
     * @param OAuthTokenDTO $authTokenDTO
     * @param int $date
     * @return array
     * @throws FatSecretException
     */
    public function getFoodEntry(OAuthTokenDTO $authTokenDTO, int $date): array
    {
        return $this->fatSecretService->getFoodEntry($authTokenDTO, $date);
    }
}
