<?php

namespace App\Providers;

use App\FatSecret\FatSecretService;
use App\FatSecret\FatSecretServiceInterface;
use App\FatSecret\FatSecretServiceLoggerDecorator;
use Illuminate\Support\ServiceProvider;

class FatSecretServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(FatSecretServiceInterface::class, function ($app) {
            return new FatSecretServiceLoggerDecorator($app->make(FatSecretService::class));
        });
    }
}
