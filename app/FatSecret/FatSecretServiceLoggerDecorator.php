<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Dto\WeightDto;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\CredentialsException;
use App\FatSecret\Exceptions\ResponseDecodeException;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use League\OAuth1\Client\Credentials\CredentialsException as LeagueCredentialsException;

class FatSecretServiceLoggerDecorator implements FatSecretServiceInterface
{
    public function __construct(private readonly FatSecretServiceInterface $fatSecretService)
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
    public function getWeightByDate(OAuthTokenDto $authTokenDTO, Carbon $date): WeightDto
    {
        try {
          return  $this->fatSecretService->getWeightByDate($authTokenDTO, $date);
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
    public function getFoodEntryByDate(OAuthTokenDto $authTokenDTO, Carbon $date): FoodEntryDto
    {
        try {
            return $this->fatSecretService->getFoodEntryByDate($authTokenDTO, $date);
        } catch (GuzzleException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new RequestErrorException();
        } catch (\JsonException $exception) {
            Log::error($exception->getMessage(), $exception);
            throw new ResponseDecodeException();
        }
    }
}
