<?php

namespace App\FatSecret;

use App\FatSecret\Dto\OAuthTokenDto;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class FatSecretClient
{
    public function __construct(
        private FatSecret $fatSecret,
        private Client $client
    ) {
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @param array $parameters
     * @return mixed
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function get(OAuthTokenDto $authTokenDTO, array $parameters): array
    {
        $this->buildCredentials($authTokenDTO);
        $parameters = $this->buildRequestParameters($parameters);
        $parameters['oauth_signature'] = $this->signRequest($parameters);
        $response = $this->client->get(FatSecret::FATSECRET_URL . "?" . http_build_query($parameters));
        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param OAuthTokenDto $authTokenDTO
     * @return void
     */
    private function buildCredentials(OAuthTokenDto $authTokenDTO): void
    {
        $this->fatSecret->setUserToken($authTokenDTO->getOAuthToken());
        $this->fatSecret->setUserSecret($authTokenDTO->getOAuthTokenSecret());
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function buildRequestParameters(array $parameters): array
    {
        $parameters = array_merge(
            $this->getDefaultParams(),
            $parameters
        );

        ksort($parameters);

        return $parameters;
    }

    /**
     * @return array
     */
    private function getDefaultParams(): array
    {
        return [
            "format" => "json",
            "oauth_consumer_key" => $this->fatSecret->getKey(),
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_token" => $this->fatSecret->getUserToken(),
            "oauth_version" => "1.0"
        ];
    }

    /**
     * @param array $parameters
     * @param string $method
     * @return string
     */
    private function signRequest(array $parameters, string $method = 'GET'): string
    {
        $baseString = implode('&',
            [
                $method,
                rawurlencode(FatSecret::FATSECRET_URL),
                rawurlencode(http_build_query($parameters))
            ]
        );

        $signingKey = $this->fatSecret->getSecret() . "&" . rawurlencode($this->fatSecret->getUserSecret());
        return base64_encode(hash_hmac("sha1", $baseString, $signingKey, true));
    }
}
