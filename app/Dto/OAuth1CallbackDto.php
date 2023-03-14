<?php

namespace App\Dto;

use Spatie\LaravelData\Data;

class OAuth1CallbackDto extends Data
{
    public function __construct(
        public string $oauth_token,
        public string $oauth_verifier
    )
    {
    }
}
