<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Nutgram $bot): JsonResponse
    {
        try {
            $bot->run();

            // Log webhook info after processing
            Log::info('Telegram webhook processed', [
                'update_id' => $bot->update()?->update_id,
                'user_id' => $bot->userId(),
                'chat_id' => $bot->chatId(),
                'has_update' => $bot->update() !== null,
                'message_text' => $bot->message()?->text,
                'callback_data' => $bot->callbackQuery()?->data
            ]);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'raw_input' => file_get_contents('php://input')
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
