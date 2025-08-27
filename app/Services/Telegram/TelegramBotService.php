<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Telegram\Commands\StartCommand;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    public function __construct(
        private TelegramUserService $userService,
        private DateSelectionService $dateService,
        private MeasurementService $measurementService,
        private FatSecretSyncService $fatSecretService
    ) {}

    public function setupBot(Nutgram $bot): void
    {
        $bot->registerCommand(StartCommand::class);
//        $this->setupCommands($bot);
        $this->setupCallbackHandlers($bot);
        $this->setupErrorHandlers($bot);
    }

    private function setupCommands(Nutgram $bot): void
    {
        $bot->onCommand('start', function (Nutgram $bot) {
            $this->handleStartCommand($bot);
        });

        $bot->onCommand('help', function (Nutgram $bot) {
            $this->handleHelpCommand($bot);
        });

        $bot->onCommand('measurements', function (Nutgram $bot) {
            $this->handleMeasurementsCommand($bot);
        });

        $bot->onCommand('sync', function (Nutgram $bot) {
            $this->handleSyncCommand($bot);
        });

        $bot->onCommand('link', function (Nutgram $bot) {
            $this->handleLinkCommand($bot);
        });
    }

    private function setupCallbackHandlers(Nutgram $bot): void
    {
        $bot->onCallbackQuery(function (Nutgram $bot) {
            try {
                $callbackData = $bot->callbackQuery()->data;

                // Answer callback query first to prevent timeout
                try {
                    $bot->answerCallbackQuery();
                } catch (\Exception $answerError) {
                    Log::warning('Failed to answer callback query', [
                        'error' => $answerError->getMessage(),
                        'callback_data' => $callbackData
                    ]);
                }

                if (str_starts_with($callbackData, 'date_select_')) {
                    $data = substr($callbackData, strlen('date_select_'));
                    $this->dateService->handleDateSelection($bot, $data);
                } elseif (str_starts_with($callbackData, 'date_confirmed_')) {
                    $context = substr($callbackData, strlen('date_confirmed_'));
                    $this->handleDateConfirmed($bot, $context);
                } elseif ($callbackData === 'measurements_start') {
                    $this->handleMeasurementsCommand($bot);
                } elseif (str_starts_with($callbackData, 'measurement_')) {
                    $data = substr($callbackData, strlen('measurement_'));
                    $this->measurementService->handleMeasurementCallback($bot, $data);
                } elseif ($callbackData === 'sync_start') {
                    $this->handleSyncCommand($bot);
                } elseif (str_starts_with($callbackData, 'sync_')) {
                    $data = substr($callbackData, strlen('sync_'));
                    $this->fatSecretService->handleSyncCallback($bot, $data);
                } elseif ($callbackData === 'start') {
                    $this->handleStartCommand($bot);
                } elseif ($callbackData === 'help') {
                    $this->handleHelpCommand($bot);
                } elseif ($callbackData === 'link_new') {
                    $this->showLinkInstructions($bot);
                } elseif ($callbackData === 'check_link') {
                    $this->checkLinkStatus($bot);
                } elseif ($callbackData === 'cancel') {
                    try {
                        $bot->editMessageText('❌ Операция отменена');
                    } catch (\Exception $e) {
                        // If we can't edit the message, send a new one
                        $bot->sendMessage('❌ Операция отменена');
                    }
                } else {
                    // Handle unknown callback
                    Log::warning('Unknown callback data received', ['data' => $callbackData]);
                }
            } catch (\Exception $e) {
                Log::error('Error handling callback query', [
                    'error' => $e->getMessage(),
                    'callback_data' => $bot->callbackQuery()->data ?? 'null',
                    'user_id' => $bot->userId()
                ]);

                try {
                    $bot->sendMessage('❌ Произошла ошибка при обработке команды. Попробуйте еще раз.');
                } catch (\Exception $sendError) {
                    Log::error('Failed to send error message', ['error' => $sendError->getMessage()]);
                }
            }
        });

        $bot->onMessage(function (Nutgram $bot) {
            // Only handle text messages that are not commands
            if ($bot->message()->text && !str_starts_with($bot->message()->text, '/')) {
                $this->measurementService->handleManualInput($bot);
            }
        });
    }

    private function setupErrorHandlers(Nutgram $bot): void
    {
        $bot->onApiError(function (Nutgram $bot, \Throwable $exception) {
            Log::error('Telegram API Error', [
                'error' => $exception->getMessage(),
                'chat_id' => $bot->chatId(),
                'user_id' => $bot->userId()
            ]);

            $bot->sendMessage('❌ Произошла ошибка. Попробуйте позже.');
        });

        $bot->onException(function (Nutgram $bot, \Throwable $exception) {
            Log::error('Telegram Bot Exception', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'chat_id' => $bot->chatId(),
                'user_id' => $bot->userId()
            ]);

            $bot->sendMessage('❌ Произошла техническая ошибка. Администратор уведомлен.');
        });
    }

    private function handleStartCommand(Nutgram $bot): void
    {

    }

    private function handleHelpCommand(Nutgram $bot): void
    {
    }

    private function handleMeasurementsCommand(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);
        if (!$user) {
            $bot->sendMessage('❌ Пожалуйста, сначала выполните команду /start');
            return;
        }

        $this->dateService->showDateSelection($bot, 'measurements');
    }

    private function handleSyncCommand(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);
        if (!$user) {
            $bot->sendMessage('❌ Пожалуйста, сначала выполните команду /start');
            return;
        }

        $this->fatSecretService->showSyncMenu($bot, $user);
    }

    private function handleDateConfirmed(Nutgram $bot, string $context): void
    {
        Log::info('handleDateConfirmed called', ['context' => $context, 'user_id' => $bot->userId()]);

        if ($context === 'measurements') {
            Log::info('Routing to measurements');
            $this->measurementService->showMeasurementMenu($bot);
        } elseif (str_starts_with($context, 'sync_')) {
            // Handle sync date confirmations (sync_weight, sync_food, etc.)
            $syncType = substr($context, strlen('sync_'));
            Log::info('Routing to sync execution', ['sync_type' => $syncType]);
            $this->fatSecretService->executeSyncWithDateRange($bot, $syncType);
        } elseif (in_array($context, ['weight', 'food', 'all'])) {
            // Handle legacy sync contexts
            Log::info('Routing to legacy sync execution', ['context' => $context]);
            $this->fatSecretService->executeSyncWithDateRange($bot, $context);
        } else {
            Log::warning('Unknown date confirmation context', ['context' => $context]);
        }
    }

    private function handleLinkCommand(Nutgram $bot): void
    {

    }

    private function showLinkInstructions(Nutgram $bot): void
    {

    }

    private function generateLinkCode(int $telegramId): string
    {

    }

    private function checkLinkStatus(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);

        if ($user && $user->telegram_id) {
            $text = "✅ *Аккаунт успешно привязан!*\n\n";
            $text .= "👤 Имя: {$user->name}\n";
            $text .= "📧 Email: {$user->email}\n\n";
            $text .= "Теперь вы можете использовать все функции бота!";

            $keyboard = InlineKeyboardMarkup::make()
                ->addRow(
                    InlineKeyboardButton::make('🔙 Главное меню', callback_data: 'start')
                );

            $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
        } else {
            $text = "❌ *Аккаунт не привязан*\n\n";
            $text .= "Убедитесь, что вы:\n";
            $text .= "• Вошли в свой аккаунт на сайте\n";
            $text .= "• Ввели правильный код привязки\n";
            $text .= "• Код не истек (действует 15 минут)";

            $keyboard = InlineKeyboardMarkup::make()
                ->addRow(
                    InlineKeyboardButton::make('🔄 Проверить еще раз', callback_data: 'check_link'),
                    InlineKeyboardButton::make('🔄 Новый код', callback_data: 'link_new')
                );

            $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
        }
    }
}
