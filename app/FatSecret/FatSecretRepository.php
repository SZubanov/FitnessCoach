<?php

namespace App\FatSecret;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use League\OAuth1\Client\Credentials\TemporaryCredentials;

class FatSecretRepository
{
    /**
     * @param TemporaryCredentials $temporaryCredentials
     * @param int $userId
     * @return void
     */
    public function storeTemporaryCredentials(TemporaryCredentials $temporaryCredentials, int $userId): void
    {
        Cache::put(
            'fatsecret:temp_cred:user:' . $temporaryCredentials->getIdentifier(),
            [
                'userId' => $userId,
                'temporaryCredentials' => $temporaryCredentials,
            ],
            $seconds = 86400
        );
    }

    public function getTemporaryCredentials(string $identifier)
    {
        return Cache::get('fatsecret:temp_cred:user:' . $identifier);
    }

    /**
     * @param int $userId
     * @param string $userOAuthToken
     * @param string $userOAuthSecret
     * @return void
     */
    public function updateAccessToken(int $userId, string $userOAuthToken, string $userOAuthSecret): void
    {
        DB::table('users')
            ->where('id', $userId)
            ->update(
                [
                    'oauth_token' => $userOAuthToken,
                    'oauth_token_secret' => $userOAuthSecret
                ]
            );
    }
}
