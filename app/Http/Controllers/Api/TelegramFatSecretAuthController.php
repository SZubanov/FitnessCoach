<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Telegram\TelegramFatSecretService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramFatSecretAuthController extends Controller
{
    public function initiate(Request $request, TelegramFatSecretService $telegramFatSecretService): JsonResponse
    {
        $telegramId = $request->input('telegram_id');
        
        if (!$telegramId) {
            return response()->json(['error' => 'Telegram ID is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        try {
            $authorizationUrl = $telegramFatSecretService->initiateOAuthForTelegram($user);
            
            return response()->json([
                'success' => true,
                'authorization_url' => $authorizationUrl,
                'message' => 'Откройте ссылку для подключения FatSecret',
                'user_id' => $user->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('FatSecret OAuth initiation failed for Telegram user', [
                'telegram_id' => $telegramId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to initiate OAuth flow: ' . $e->getMessage()
            ], 500);
        }
    }
}