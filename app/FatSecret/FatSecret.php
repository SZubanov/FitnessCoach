<?php

namespace App\FatSecret;

use GuzzleHttp\Exception\BadResponseException;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;

class FatSecret
{
    /**
     * @var string
     */
    private mixed $key;
    /**
     * @var string
     */
    private mixed $secret;

    public function __construct() {
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
}
