<?php

namespace App\FatSecret;

use GuzzleHttp\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;

class FatSecret extends Server
{
    private const REQUEST_TOKEN_URL = "https://www.fatsecret.com/oauth/request_token";
    private const AUTHORIZE_URL = "https://www.fatsecret.com/oauth/authorize";
    private const ACCESS_TOKEN_URL = "https://www.fatsecret.com/oauth/access_token";

    public function urlTemporaryCredentials(): string
    {
        return self::REQUEST_TOKEN_URL;
    }

    public function urlAuthorization(): string
    {
        return self::AUTHORIZE_URL;
    }

    public function urlTokenCredentials(): string
    {
        return self::ACCESS_TOKEN_URL;
    }

    public function urlUserDetails()
    {
        return null;
    }

    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws CredentialsException
     */
    public function getTemporaryCredentials()
    {
        $uri = $this->urlTemporaryCredentials();
        $client = $this->createHttpClient();

        $queryParams = $this->temporaryCredentialsProtocolQueryParams($uri);

        try {
            $response = $client->get($uri . '?' . $queryParams);
            return $this->createTemporaryCredentials((string) $response->getBody());
        } catch (BadResponseException $e) {
            $this->handleTemporaryCredentialsBadResponse($e);
        }
    }

    protected function temporaryCredentialsProtocolQueryParams($uri): string
    {
        $parameters = array_merge($this->baseProtocolParameters(), [
            'oauth_callback' => $this->clientCredentials->getCallbackUri(),
        ]);

        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'GET');

        return $this->normalizeProtocolParameters($parameters);
    }

    protected function normalizeProtocolParameters(array $parameters): string
    {
        array_walk($parameters, static function (&$value, $key) {
            $value = rawurlencode($key) . '=' . rawurlencode($value);
        });

        return implode('&', $parameters);
    }

}
