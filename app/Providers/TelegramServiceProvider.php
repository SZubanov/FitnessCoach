<?php

namespace App\Providers;

use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramUserService;
use App\Services\Telegram\DateSelectionService;
use App\Services\Telegram\MeasurementService;
use App\Services\Telegram\FatSecretSyncService;
use App\Services\Telegram\TelegramFatSecretService;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Nutgram::class, function ($app) {
            $bot = new Nutgram(config('nutgram.token'));
            $bot->setRunningMode(Webhook::class);
            return $bot;
        });

        $this->app->singleton(TelegramUserService::class);
        $this->app->singleton(DateSelectionService::class);
        $this->app->singleton(MeasurementService::class);
        $this->app->singleton(FatSecretSyncService::class);
        $this->app->singleton(TelegramFatSecretService::class);
        $this->app->singleton(TelegramBotService::class);
    }

    public function boot(): void
    {
        //
    }
}
