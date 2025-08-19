<?php

namespace App\Services\Telegram;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
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
        $this->setupCommands($bot);
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
        $telegramUser = $bot->user();
        $user = $this->userService->findOrCreateUser($telegramUser);

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('📏 Замеры тела', callback_data: 'measurements_start'),
                InlineKeyboardButton::make('🔄 Синхронизация', callback_data: 'sync_start')
            )
            ->addRow(
                InlineKeyboardButton::make('❓ Помощь', callback_data: 'help')
            );

        $welcomeText = "👋 Добро пожаловать в FitnessCoach!\n\n";
        $welcomeText .= "Этот бот поможет вам:\n";
        $welcomeText .= "• 📏 Записывать замеры тела\n";
        $welcomeText .= "• 🔄 Синхронизировать данные с FatSecret\n\n";
        $welcomeText .= "Выберите действие:";

        $bot->sendMessage($welcomeText, reply_markup: $keyboard);
    }

    private function handleHelpCommand(Nutgram $bot): void
    {
        $helpText = "❓ *Справка по командам*\n\n";
        $helpText .= "/start - Начать работу с ботом\n";
        $helpText .= "/measurements - Добавить замеры тела\n";
        $helpText .= "/sync - Синхронизация с FatSecret\n";
        $helpText .= "/link - Привязать к существующему аккаунту\n";
        $helpText .= "/help - Показать эту справку\n\n";
        
        $helpText .= "📅 *Работа с датами:*\n";
        $helpText .= "• Быстрый выбор: Сегодня, Вчера, 2 дня назад\n";
        $helpText .= "• Календарь для точной даты\n";
        $helpText .= "• Диапазон дат для синхронизации\n\n";
        
        $helpText .= "📏 *Замеры тела:*\n";
        $helpText .= "Поддерживаемые типы: грудь, талия, бедра, бицепс, бедро\n\n";
        
        $helpText .= "🔄 *Синхронизация:*\n";
        $helpText .= "• Синхронизация веса\n";
        $helpText .= "• Синхронизация питания\n";
        $helpText .= "• Выборочная синхронизация по датам";

        $bot->sendMessage($helpText, parse_mode: 'Markdown');
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
        if ($context === 'measurements') {
            $this->measurementService->showMeasurementMenu($bot);
        }
    }

    private function handleLinkCommand(Nutgram $bot): void
    {
        $telegramUser = $bot->user();
        $existingUser = $this->userService->getCurrentUser($bot);
        
        if ($existingUser) {
            $keyboard = InlineKeyboardMarkup::make()
                ->addRow(
                    InlineKeyboardButton::make('🔗 Привязать другой аккаунт', callback_data: 'link_new'),
                    InlineKeyboardButton::make('❌ Отмена', callback_data: 'cancel')
                );

            $text = "🔗 *Привязка аккаунта*\n\n";
            $text .= "У вас уже есть привязанный аккаунт:\n";
            $text .= "👤 {$existingUser->name}\n";
            $text .= "📧 {$existingUser->email}\n\n";
            $text .= "Хотите привязать другой аккаунт?";

            $bot->sendMessage($text, reply_markup: $keyboard, parse_mode: 'Markdown');
        } else {
            $this->showLinkInstructions($bot);
        }
    }

    private function showLinkInstructions(Nutgram $bot): void
    {
        $telegramUser = $bot->user();
        $linkCode = $this->generateLinkCode($bot->userId());
        
        $text = "🔗 *Привязка к существующему аккаунту*\n\n";
        $text .= "Для привязки вашего Telegram к существующему аккаунту:\n\n";
        $text .= "1️⃣ Откройте веб-сайт приложения\n";
        $text .= "2️⃣ Войдите в свой аккаунт\n";
        $text .= "3️⃣ Перейдите в настройки\n";
        $text .= "4️⃣ Введите код привязки: `{$linkCode}`\n\n";
        $text .= "🕒 Код действителен 15 минут\n\n";
        $text .= "📱 *Адрес сайта:* " . (config('app.url') !== 'http://localhost' ? config('app.url') : 'https://yourdomain.com');

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('🔄 Проверить привязку', callback_data: 'check_link'),
                InlineKeyboardButton::make('🔄 Новый код', callback_data: 'link_new')
            )
            ->addRow(
                InlineKeyboardButton::make('❌ Отмена', callback_data: 'cancel')
            );

        $bot->sendMessage($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function generateLinkCode(int $telegramId): string
    {
        $code = strtoupper(substr(md5($telegramId . time()), 0, 8));
        
        // Store the link code in cache for 15 minutes
        $cacheKey = "telegram_link_code_{$code}";
        \Cache::put($cacheKey, $telegramId, now()->addMinutes(15));
        
        return $code;
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