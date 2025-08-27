<?php

namespace App\Telegram\Handlers;

use App\Telegram\Services\TelegramMessageService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class ErrorHandlers
{
    public function __construct(readonly private TelegramMessageService $telegramMessageService)
    {

    }

    public function setup(Nutgram $bot): void
    {
        $bot->onApiError(function (Nutgram $bot, \Throwable $exception) {
            Log::error('Telegram API Error', [
                'error' => $exception->getMessage(),
                'chat_id' => $bot->chatId(),
                'user_id' => $bot->userId()
            ]);

            $this->telegramMessageService->sendMessageApiError($bot);
        });


        $bot->onException(function (Nutgram $bot, \Throwable $exception) {
            Log::error('Telegram Bot Exception', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'chat_id' => $bot->chatId(),
                'user_id' => $bot->userId()
            ]);

            $this->telegramMessageService->sendMessageApiError($bot);
        });
    }
}
