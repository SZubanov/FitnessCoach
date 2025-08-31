<?php

namespace App\Telegram\Providers;

use App\Telegram\Commands\StartCommand;
use App\Telegram\Commands\HelpCommand;
use App\Telegram\Middleware\AccountLinkMiddleware;
use App\Telegram\Middleware\FatSecretMiddleware;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerCommands();
        $this->registerMiddleware();
        $this->registerConversations();
    }
    
    private function registerCommands()
    {
        /** @var Nutgram $bot */
        $bot = app(Nutgram::class);
        
        // Register commands
        $bot->onCommand('start', StartCommand::class);
        $bot->onCommand('help', HelpCommand::class);
        
        // Register direct command shortcuts
        $bot->onCommand('measurements', function (Nutgram $bot) {
            app(\App\Telegram\Menus\MeasurementsMenu::class)->start($bot);
        })->middleware(AccountLinkMiddleware::class);
        
        $bot->onCommand('sync', function (Nutgram $bot) {
            app(\App\Telegram\Menus\SyncMenu::class)->start($bot);
        })->middleware([AccountLinkMiddleware::class, FatSecretMiddleware::class]);
        
        $bot->onCommand('macros', function (Nutgram $bot) {
            app(\App\Telegram\Menus\MacrosMenu::class)->start($bot);
        })->middleware(AccountLinkMiddleware::class);
        
        $bot->onCommand('weight', function (Nutgram $bot) {
            app(\App\Telegram\Menus\WeightMenu::class)->start($bot);
        })->middleware(AccountLinkMiddleware::class);
    }
    
    private function registerMiddleware()
    {
        // Global middleware registration would go here if needed
        // Currently middleware is applied per command/handler
    }
    
    private function registerConversations()
    {
        /** @var Nutgram $bot */
        $bot = app(Nutgram::class);
        
        // Register conversation step handlers
        $bot->onText(function (Nutgram $bot) {
            // This catches text input during conversations
            // The conversation system will handle routing to appropriate step
        });
        
        // Error handlers
        $bot->onException(function (Nutgram $bot, \Throwable $exception) {
            logger()->error('Telegram Bot Exception', [
                'exception' => $exception->getMessage(),
                'user_id' => $bot->userId(),
                'update' => $bot->update()
            ]);
            
            $bot->sendMessage(
                "❌ **Произошла ошибка**\n\n" .
                "Попробуйте позже или обратитесь в поддержку"
            );
        });
        
        // Fallback for unknown commands/messages
        $bot->fallback(function (Nutgram $bot) {
            app(\App\Telegram\Menus\MainMenu::class)->start($bot);
        });
    }
    
    public function register()
    {
        // Bind services to container if needed
        $this->app->singleton(\App\Telegram\Services\DateValidationService::class);
    }
}