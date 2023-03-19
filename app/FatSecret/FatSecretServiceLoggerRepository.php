<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use League\OAuth1\Client\Credentials\CredentialsException;

class FatSecretServiceLoggerRepository
{
    public function __construct(private FatSecretService $fatSecretService)
    {

    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws CredentialsException
     */
    public function getRequestToken(): void
    {
        try {
            $this->fatSecretService->getRequestToken();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), $exception);
            throw $exception;
        }
    }

    /**
     * @throws FatSecretException
     * @throws GuzzleException
     */
    public function getAccessToken(OAuth1CallbackDto $oAuth1CallbackDto): void
    {
        try {
            $this->fatSecretService->getAccessToken($oAuth1CallbackDto);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), $exception);
            throw $exception;
        }
    }

    /**
     * @param int $date
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getMonthWeights(int $date): array
    {
        try {
          return  $this->fatSecretService->getMonthWeights($date);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), $exception);
            throw $exception;
        }
    }
}
