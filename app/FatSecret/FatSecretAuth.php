<?php

namespace App\FatSecret;

use App\Dto\OAuth1CallbackDto;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;

class FatSecretAuth extends Server
{
    private const REQUEST_TOKEN_URL = "https://www.fatsecret.com/oauth/request_token",
        AUTHORIZE_URL = "https://www.fatsecret.com/oauth/authorize",
        ACCESS_TOKEN_URL = "https://www.fatsecret.com/oauth/access_token";

    private const SIGNATURE_ACCESS_TOKEN_METHOD = 'POST';

    private GuzzleHttpClient $client;

    public function __construct(private FatSecret $fatSecret, GuzzleHttpClient $client)
    {
        $this->client = $client;
        $clientCredentials = [
            'identifier' => $this->fatSecret->getKey(),
            'secret' => $this->fatSecret->getSecret(),
            'callback_uri' => config('services.fatsecret.callback_uri'),
        ];
        parent::__construct($clientCredentials);
    }

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
    }

    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userUid($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
    }

    /**
     * @return TemporaryCredentials
     * @throws CredentialsException
     * @throws GuzzleException
     */
    public function getTemporaryCredentials(): TemporaryCredentials
    {
        $uri = $this->urlTemporaryCredentials();

        $queryParams = $this->temporaryCredentialsProtocolQueryParams($uri);

        try {
            $response = $this->client->get($uri . '?' . http_build_query($queryParams));
            return $this->createTemporaryCredentials((string)$response->getBody());
        } catch (BadResponseException $e) {
            $this->handleTemporaryCredentialsBadResponse($e);
        }
    }

    /**
     * @param $uri
     * @return array|string[]
     */
    private function temporaryCredentialsProtocolQueryParams($uri): array
    {
        $parameters = array_merge(
            $this->baseProtocolParameters(),
            [
                'oauth_callback' => $this->clientCredentials->getCallbackUri()
            ]
        );

        $parameters['oauth_signature'] = $this->signature->sign($uri, $parameters, 'GET');
        return $parameters;
    }

    /**
     * @param TemporaryCredentials $temporaryCredentials
     * @param OAuth1CallbackDto $OAuth1CallbackDto
     * @return string[]
     * @throws GuzzleException
     */
    public function getAccessToken(
        TemporaryCredentials $temporaryCredentials,
        OAuth1CallbackDto $OAuth1CallbackDto
    ): array {
        if ($OAuth1CallbackDto->oauth_token !== $temporaryCredentials->getIdentifier()) {
            throw new \InvalidArgumentException(
                'Temporary identifier passed back by server does not match that of stored temporary credentials.
                Potential man-in-the-middle.'
            );
        }

        $response = $this->client->post(
            $this->urlTokenCredentials(),
            [
                'form_params' => $this->getAccessTokenQueryParams($temporaryCredentials, $OAuth1CallbackDto),
            ]
        );

        parse_str((string)$response->getBody(), $accessToken);

        return [$accessToken['oauth_token'], $accessToken['oauth_token_secret']];
    }

    /**
     * @param TemporaryCredentials $temporaryCredentials
     * @param OAuth1CallbackDto $OAuth1CallbackDto
     * @return string[]
     */
    private function getAccessTokenQueryParams(
        TemporaryCredentials $temporaryCredentials,
        OAuth1CallbackDto $OAuth1CallbackDto
    ): array {
        $parameters = array_merge(
            $this->baseProtocolParameters(),
            [
                'oauth_token' => $OAuth1CallbackDto->oauth_token,
                'oauth_verifier' => $OAuth1CallbackDto->oauth_verifier,
            ]
        );

        ksort($parameters);

        $parameters['oauth_signature'] = $this->signatureAccessToken($parameters, $temporaryCredentials->getSecret());

        return $parameters;
    }

    /**
     * @param array $parameters
     * @param string $userSecret
     * @return string
     */
    private function signatureAccessToken(array $parameters, string $userSecret): string
    {
        $baseString = implode('&',
            [
                self::SIGNATURE_ACCESS_TOKEN_METHOD,
                rawurlencode($this->urlTokenCredentials()),
                rawurlencode(http_build_query($parameters))
            ]
        );

        $oauthSignatureKey = implode('&',
            [
                $this->clientCredentials->getSecret(),
                rawurlencode($userSecret)
            ]
        );

        return base64_encode(hash_hmac('sha1', $baseString, $oauthSignatureKey, true));
    }
}
