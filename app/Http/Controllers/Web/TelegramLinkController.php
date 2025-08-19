<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Telegram\TelegramUserService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramLinkController extends Controller
{
    public function __construct(private TelegramUserService $telegramUserService) {}

    public function link(Request $request): RedirectResponse
    {
        $request->validate([
            'link_code' => 'required|string|size:8'
        ]);

        $linkCode = strtoupper($request->input('link_code'));
        $cacheKey = "telegram_link_code_{$linkCode}";
        
        $telegramId = Cache::get($cacheKey);
        
        if (!$telegramId) {
            return back()->withErrors(['link_code' => 'Неверный или истекший код привязки']);
        }

        $currentUser = Auth::user();
        
        // Check if this Telegram ID is already linked to another account
        $existingLinkedUser = User::where('telegram_id', $telegramId)->first();
        
        if ($existingLinkedUser && $existingLinkedUser->id !== $currentUser->id) {
            return back()->withErrors(['link_code' => 'Этот Telegram аккаунт уже привязан к другому пользователю']);
        }

        try {
            // Update current user with Telegram ID
            $currentUser->update([
                'telegram_id' => $telegramId
            ]);

            // Clear the used code
            Cache::forget($cacheKey);

            Log::info('Telegram account linked successfully', [
                'user_id' => $currentUser->id,
                'telegram_id' => $telegramId,
                'link_code' => $linkCode
            ]);

            return back()->with('success', 'Telegram аккаунт успешно привязан!');

        } catch (\Exception $e) {
            Log::error('Failed to link Telegram account', [
                'user_id' => $currentUser->id,
                'telegram_id' => $telegramId,
                'link_code' => $linkCode,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['link_code' => 'Ошибка при привязке аккаунта']);
        }
    }

    public function unlink(): RedirectResponse
    {
        $currentUser = Auth::user();

        if (!$currentUser->telegram_id) {
            return back()->withErrors(['telegram' => 'Telegram аккаунт не привязан']);
        }

        try {
            $currentUser->update([
                'telegram_id' => null,
                'telegram_username' => null
            ]);

            Log::info('Telegram account unlinked', [
                'user_id' => $currentUser->id,
                'telegram_id' => $currentUser->telegram_id
            ]);

            return back()->with('success', 'Telegram аккаунт успешно отвязан');

        } catch (\Exception $e) {
            Log::error('Failed to unlink Telegram account', [
                'user_id' => $currentUser->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['telegram' => 'Ошибка при отвязке аккаунта']);
        }
    }
}