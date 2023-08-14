<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\CredentialsException;
use App\FatSecret\Exceptions\ResponseDecodeException;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use League\OAuth1\Client\Credentials\CredentialsException as LeagueCredentialsException;

class FatSecretServiceLoggerDecorator implements FatSecretServiceInterface
{
    public function __construct(private FatSecretServiceInterface $fatSecretService)
    {

    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getMonthWeights(OAuthTokenDto $authTokenDTO, Carbon $date): array
    {
        try {
          return  $this->fatSecretService->getMonthWeights($authTokenDTO, $date);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        } catch (\JsonException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new ResponseDecodeException();
        }
    }

    /**
     * @inheritDoc
     */
    public function getFoodEntry(OAuthTokenDto $authTokenDTO, Carbon $date): FoodEntryDto
    {
        try {
            return $this->fatSecretService->getFoodEntry($authTokenDTO, $date);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        } catch (\JsonException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new ResponseDecodeException();
        }
    }
}
