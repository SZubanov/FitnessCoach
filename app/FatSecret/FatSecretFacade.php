<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Exceptions\FatSecretException;

class FatSecretFacade
{
    public function __construct(public FatSecretServiceLoggerRepository $fatSecretService)
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
     * @param int $date
     * @return array
     * @throws FatSecretException
     */
    public function getMonthWeights(int $date): array
    {
      return $this->fatSecretService->getMonthWeights($date);
    }
}
