<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\CredentialsException;
use App\FatSecret\Exceptions\ResponseDecodeException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use League\OAuth1\Client\Credentials\CredentialsException as LeagueCredentialsException;

class FatSecretServiceLoggerRepository
{
    public function __construct(private FatSecretService $fatSecretService)
    {

    }

    /**
     * @return void
     * @throws RequestErrorException|Exceptions\CredentialsException
     */
    public function getRequestToken(): void
    {
        try {
            $this->fatSecretService->getRequestToken();
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        } catch (LeagueCredentialsException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new CredentialsException();
        }
    }

    /**
     * @throws FatSecretException
     */
    public function getAccessToken(OAuth1CallbackDto $oAuth1CallbackDto): void
    {
        try {
            $this->fatSecretService->getAccessToken($oAuth1CallbackDto);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        }
    }

    /**
     * @param int $date
     * @return array
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     */
    public function getMonthWeights(int $date): array
    {
        try {
          return  $this->fatSecretService->getMonthWeights($date);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        } catch (\JsonException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new ResponseDecodeException();
        }
    }

    /**
     * @param int $date
     * @return array
     * @throws RequestErrorException
     * @throws ResponseDecodeException
     */
    public function getFoodEntry(int $date)
    {
        try {
            return $this->fatSecretService->getFoodEntry($date);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        } catch (\JsonException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new ResponseDecodeException();
        }
    }
}
