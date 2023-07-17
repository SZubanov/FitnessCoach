<?php

namespace App\FatSecret;

use App\FatSecret\Dto\OAuthTokenDTO;

class FatSecret
{
    public const FATSECRET_URL = 'https://platform.fatsecret.com/rest/server.api';

    public const GET_MONTH_WEIGHT_METHOD = 'weights.get_month',
        GET_FOOD_ENTRY_METHOD = 'food_entries.get';

    /**
     * @var string
     */
    private string $key;
    /**
     * @var string
     */
    private string $secret;

    /**
     * @var string
     */
    private string $userToken;

    /**
     * @var string
     */
    private string $userSecret;

    public function __construct()
    {
        $this->key = config('services.fatsecret.key');
        $this->secret = config('services.fatsecret.secret');
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getUserToken(): string
    {
        return $this->userToken;
    }

    /**
     * @param string $userToken
     */
    public function setUserToken(string $userToken): void
    {
        $this->userToken = $userToken;
    }

    /**
     * @return string
     */
    public function getUserSecret(): string
    {
        return $this->userSecret;
    }

    /**
     * @param string $userSecret
     */
    public function setUserSecret(string $userSecret): void
    {
        $this->userSecret = $userSecret;
    }
}
