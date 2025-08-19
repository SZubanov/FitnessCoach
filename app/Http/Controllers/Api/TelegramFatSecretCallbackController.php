<?php

namespace App\Http\Controllers\Api;

use App\Dto\OAuth1CallbackDto;
use App\FatSecret\FatSecretFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\OAuth1CallbackRequest;
use App\Models\User;
use App\Services\Telegram\TelegramFatSecretService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Exceptions\InvalidDataClass;

class TelegramFatSecretCallbackController extends Controller
{
    /**
     * @throws InvalidDataClass
     */
    public function __invoke(
        OAuth1CallbackRequest $request, 
        FatSecretFacade $fatSecretFacade,
        TelegramFatSecretService $telegramFatSecretService
    ): RedirectResponse {
        /** @var OAuth1CallbackDto $userAuthCredentials */
        $userAuthCredentials = $request->getData();
        
        // Get the user from cache using the OAuth token
        $oauthToken = $userAuthCredentials->oauth_token;
        $userId = $telegramFatSecretService->getUserIdFromToken($oauthToken);
        
        if (!$userId) {
            Log::error('No user ID found in cache for Telegram OAuth callback', [
                'oauth_token' => $oauthToken
            ]);
            return redirect()->route('telegram.oauth.result', ['status' => 'error', 'message' => 'OAuth session expired']);
        }

        $user = User::find($userId);
        
        if (!$user) {
            Log::error('User not found for Telegram OAuth callback', ['user_id' => $userId]);
            return redirect()->route('telegram.oauth.result', ['status' => 'error', 'message' => 'User not found']);
        }

        try {
            // Temporarily authenticate as this user for the OAuth callback
            auth()->login($user);
            
            $fatSecretFacade->getAccessToken($userAuthCredentials);
            
            // Clear the OAuth data
            $telegramFatSecretService->clearOAuthData($oauthToken);
            
            Log::info('FatSecret OAuth completed successfully for Telegram user', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id
            ]);
            
            return redirect()->route('telegram.oauth.result', [
                'status' => 'success',
                'message' => 'FatSecret подключен успешно! Вернитесь в Telegram бот.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('FatSecret OAuth callback failed for Telegram user', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('telegram.oauth.result', [
                'status' => 'error',
                'message' => 'Ошибка подключения FatSecret: ' . $e->getMessage()
            ]);
        }
    }
}