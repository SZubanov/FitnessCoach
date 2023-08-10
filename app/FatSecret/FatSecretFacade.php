<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\FatSecretException;
use Illuminate\Support\Collection;

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
     * @param int $date
     * @return array
     * @throws FatSecretException
     */
    public function getMonthWeights(OAuthTokenDto $authTokenDTO, int $date): array
    {
      return $this->fatSecretService->getMonthWeights($authTokenDTO, $date);
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param int $date
     * @return Collection
     * @throws FatSecretException
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, int $date): Collection
    {
        return $this->fatSecretService->getFoodEntry($authTokenDTO, $date);
    }
}
