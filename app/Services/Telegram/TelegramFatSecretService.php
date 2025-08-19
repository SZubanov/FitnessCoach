<?php

namespace App\Services\Telegram;

use App\FatSecret\FatSecret;
use App\FatSecret\FatSecretAuth;
use App\Models\User;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use League\OAuth1\Client\Credentials\TemporaryCredentials;

class TelegramFatSecretService
{
    public function __construct(
        private FatSecret $fatSecret,
        private GuzzleHttpClient $httpClient
    ) {}

    public function initiateOAuthForTelegram(User $user): string
    {
        try {
            // Temporarily override the callback URL in config for this request
            $originalCallback = config('services.fatsecret.callback_uri');
            $telegramCallback = route('telegram.fatsecret.callback');
            
            // Set the Telegram callback URL in config
            config(['services.fatsecret.callback_uri' => $telegramCallback]);
            
            // Create auth instance with the new callback URL
            $auth = new FatSecretAuth($this->fatSecret, $this->httpClient);
            
            // Get temporary credentials
            $temporaryCredentials = $auth->getTemporaryCredentials();
            
            // Store temporary credentials in the same format as FatSecretRepository expects
            Cache::put(
                'fatsecret:temp_cred:user:' . $temporaryCredentials->getIdentifier(),
                [
                    'userId' => $user->id,
                    'temporaryCredentials' => $temporaryCredentials,
                ],
                now()->addMinutes(15)
            );
            
            // Also store user ID separately for our callback lookup
            Cache::put('telegram_oauth_user_' . $temporaryCredentials->getIdentifier(), $user->id, now()->addMinutes(15));
            
            // Get authorization URL
            $authorizationUrl = $auth->getAuthorizationUrl($temporaryCredentials);
            
            // Restore original callback URL
            config(['services.fatsecret.callback_uri' => $originalCallback]);
            
            Log::info('Telegram FatSecret OAuth initiated', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'auth_url' => $authorizationUrl
            ]);
            
            return $authorizationUrl;
            
        } catch (\Exception $e) {
            // Restore original callback URL on error
            if (isset($originalCallback)) {
                config(['services.fatsecret.callback_uri' => $originalCallback]);
            }
            
            Log::error('Failed to initiate Telegram FatSecret OAuth', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    
    public function getUserIdFromToken(string $oauthToken): ?int
    {
        return Cache::get('telegram_oauth_user_' . $oauthToken);
    }
    
    public function clearOAuthData(string $oauthToken): void
    {
        // Clear both the repository format and our custom format
        Cache::forget('fatsecret:temp_cred:user:' . $oauthToken);
        Cache::forget('telegram_oauth_user_' . $oauthToken);
    }
}