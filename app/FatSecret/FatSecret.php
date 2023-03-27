<?php

namespace App\FatSecret;

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
        $user = \Auth::user();
        $this->key = config('services.fatsecret.key');
        $this->secret = config('services.fatsecret.secret');
        $this->userToken = $user->oauth_token ?? null;
        $this->userSecret = $user->oauth_token_secret ?? null;
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

    /**
     * @return array
     */
    private function getDefaultParams(): array
    {
        return [
            "format" => "json",
            "oauth_consumer_key" => $this->getKey(),
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_token" => $this->getUserToken(),
            "oauth_version" => "1.0"
        ];
    }

    /**
     * @param array $parameters
     * @param string $method
     * @return string
     */
    public function signRequest(array $parameters, string $method = 'GET'): string
    {
        $baseString = implode('&',
            [
                $method,
                rawurlencode(self::FATSECRET_URL),
                rawurlencode(http_build_query($parameters))
            ]
        );

        $signingKey = $this->getSecret() . "&" . rawurlencode($this->getUserSecret());
        return base64_encode(hash_hmac("sha1", $baseString, $signingKey, true));
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function buildRequestParameters(array $parameters): array
    {
        $parameters = array_merge(
            $this->getDefaultParams(),
            $parameters
        );

        ksort($parameters);

        return $parameters;
    }
}
