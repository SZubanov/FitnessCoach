<?php

namespace App\Telegram\Providers;

use App\Telegram\Commands\StartCommand;
use App\Telegram\Commands\HelpCommand;
use App\Telegram\Commands\MenuCommand;
use App\Telegram\Commands\MeasurementsCommand;
use App\Telegram\Commands\MacrosCommand;
use App\Telegram\Commands\WeightCommand;
use App\Telegram\Commands\SyncCommand;
use App\Telegram\Commands\SettingsCommand;
use App\Telegram\Commands\AccountCommand;
use App\Telegram\Commands\FatSecretCommand;
use App\Telegram\Commands\StatusCommand;
use App\Telegram\Commands\CancelCommand;
use App\Telegram\Commands\InfoCommand;
use App\Telegram\Commands\SupportCommand;
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

        // Core Navigation Commands
        $bot->onCommand('start', StartCommand::class);
        $bot->onCommand('help', HelpCommand::class);
        $bot->onCommand('menu', MenuCommand::class);

        // Feature Shortcut Commands (with middleware)
        $bot->onCommand('measurements', MeasurementsCommand::class)
            ->middleware(AccountLinkMiddleware::class);

        $bot->onCommand('macros', MacrosCommand::class)
            ->middleware(AccountLinkMiddleware::class);

        $bot->onCommand('weight', WeightCommand::class)
            ->middleware(AccountLinkMiddleware::class);

        $bot->onCommand('sync', SyncCommand::class)
            ->middleware([AccountLinkMiddleware::class, FatSecretMiddleware::class]);

        // Admin & Management Commands
        $bot->onCommand('settings', SettingsCommand::class);
        $bot->onCommand('account', AccountCommand::class);
        $bot->onCommand('fatsecret', FatSecretCommand::class)
            ->middleware(AccountLinkMiddleware::class);
        $bot->onCommand('status', StatusCommand::class);

        // Utility Commands
        $bot->onCommand('cancel', CancelCommand::class);
        $bot->onCommand('info', InfoCommand::class);
        $bot->onCommand('support', SupportCommand::class);
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
//        $bot->onText(function (Nutgram $bot) {
//            // This catches text input during conversations
//            // The conversation system will handle routing to appropriate step
//        });

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
            $messageText = $bot->message()?->text ?? '';

            // Check if it's an unknown command (starts with /)
            if (str_starts_with($messageText, '/')) {
                $command = ltrim(explode(' ', $messageText)[0], '/');
                $helpMessage = \App\Telegram\Utilities\CommandDocumentation::handleUnknownCommand($command);
                $bot->sendMessage($helpMessage);
            }

            // Always return to main menu
            \App\Telegram\Menus\MainMenu::begin($bot);
        });
    }

    public function register()
    {
        // Bind services to container if needed
        $this->app->singleton(\App\Telegram\Services\DateValidationService::class);
        $this->app->singleton(\App\Telegram\Services\TelegramAccountService::class);
    }
}
