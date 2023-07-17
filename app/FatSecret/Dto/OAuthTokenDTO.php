<?php

namespace App\FatSecret\Dto;

class OAuthTokenDTO
{
    public function __construct(
        private string $oauthToken,
        private string $oauthTokenSecret,
    )
    {
    }

    /**
     * @return string
     */
    public function getOAuthToken(): string
    {
        return $this->oauthToken;
    }

    /**
     * @return string
     */
    public function getOAuthTokenSecret(): string
    {
        return $this->oauthTokenSecret;
    }
}
