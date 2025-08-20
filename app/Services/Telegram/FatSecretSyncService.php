<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\FatSecret\FatSecretServiceInterface;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Dto\WeightDto;
use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Exceptions\RecordNotFoundException;
use App\Contracts\Actions\Diary\StoreUserDiaryWeightInterface;
use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Dto\Web\Diary\DiaryWeightStoreDto;
use App\Dto\Web\Diary\DiaryMacrosStoreDto;
use App\Dto\UserReport\DtoFactory;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FatSecretSyncService
{
    public function __construct(
        private FatSecretServiceInterface $fatSecretService,
        private DateSelectionService $dateService,
        private TelegramUserService $userService,
        private StoreUserDiaryWeightInterface $storeWeightAction,
        private StoreUserDiaryMacrosInterface $storeMacrosAction,
        private DtoFactory $dtoFactory
    ) {}

    public function handleSyncCallback(Nutgram $bot, string $data): void
    {
        $parts = explode('_', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'start':
                $this->showSyncMenu($bot);
                break;
            case 'type':
                $syncType = $parts[1] ?? '';
                $this->executeSyncWithDateRange($bot, $syncType);
                break;
            case 'connect':
                $this->showConnectionInstructions($bot);
                break;
            case 'oauth':
                $this->handleOAuthFlow($bot, $data);
                break;
            case 'disconnect':
                $this->disconnectFatSecret($bot);
                break;
            case 'daterange':
                $this->handleDateRangeSync($bot, $data);
                break;
        }
    }

    public function showSyncMenu(Nutgram $bot, ?User $user = null): void
    {
        $user = $user ?? $this->userService->getCurrentUser($bot);
        if (!$user) {
            $bot->sendMessage('❌ Ошибка авторизации');
            return;
        }

        $isConnected = $user->isFatSecretAuthorized();
        $keyboard = InlineKeyboardMarkup::make();

        if ($isConnected) {
            $lastSync = $this->getLastSyncTimestamp($user->id);

            $keyboard->addRow(
                InlineKeyboardButton::make('🔄 Полная синхронизация', callback_data: 'sync_type_all'),
                InlineKeyboardButton::make('⚖️ Только вес', callback_data: 'sync_type_weight')
            );

            $keyboard->addRow(
                InlineKeyboardButton::make('🍎 Только питание', callback_data: 'sync_type_food'),
                InlineKeyboardButton::make('📅 Выбрать период', callback_data: 'sync_daterange')
            );

            if ($lastSync) {
                $keyboard->addRow(
                    InlineKeyboardButton::make('🔄 С последней синхронизации', callback_data: 'sync_type_since_last')
                );
            }

            $keyboard->addRow(
                InlineKeyboardButton::make('❌ Отключить FatSecret', callback_data: 'sync_disconnect'),
                InlineKeyboardButton::make('🔙 Главное меню', callback_data: 'start')
            );

            $text = "🔄 *Синхронизация с FatSecret*\n\n";
            $text .= "✅ FatSecret подключен\n";

            if ($lastSync) {
                $lastSyncDate = Carbon::parse($lastSync);
                $text .= "📅 Последняя синхронизация: {$lastSyncDate->format('d.m.Y H:i')}\n";
            }

            $text .= "\nВыберите тип синхронизации:";
        } else {
            $keyboard->addRow(
                InlineKeyboardButton::make('🔗 Подключить FatSecret', callback_data: 'sync_connect'),
                InlineKeyboardButton::make('🔙 Главное меню', callback_data: 'start')
            );

            $text = "🔄 *Синхронизация с FatSecret*\n\n";
            $text .= "❌ FatSecret не подключен\n\n";
            $text .= "Для синхронизации данных о весе и питании необходимо подключить ваш аккаунт FatSecret.\n\n";
            $text .= "После подключения вы сможете:\n";
            $text .= "• Синхронизировать данные о весе\n";
            $text .= "• Импортировать записи о питании\n";
            $text .= "• Выбирать конкретные даты для синхронизации";
        }

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    public function showConnectionInstructions(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);

        if (!$user) {
            $bot->editMessageText('❌ Ошибка: пользователь не найден');
            return;
        }

        $text = "🔗 *Подключение FatSecret*\n\n";
        $text .= "Нажмите кнопку ниже, чтобы начать процесс подключения FatSecret.\n\n";
        $text .= "Вы будете перенаправлены на сайт FatSecret для авторизации.\n\n";
        $text .= "⚠️ После авторизации вы автоматически вернетесь на страницу результата.";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('🔗 Подключить FatSecret', callback_data: 'sync_oauth_start')
            )
            ->addRow(
                InlineKeyboardButton::make('🔄 Проверить подключение', callback_data: 'sync_start'),
                InlineKeyboardButton::make('🔙 Назад', callback_data: 'sync_start')
            );

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    public function handleOAuthFlow(Nutgram $bot, string $data): void
    {
        $user = $this->userService->getCurrentUser($bot);

        if (!$user) {
            $bot->answerCallbackQuery('❌ Ошибка: пользователь не найден');
            return;
        }

        $parts = explode('_', $data);
        $action = $parts[1] ?? '';

        if ($action === 'start') {
            try {
                // Call the API to initiate OAuth
                $response = Http::post(route('telegram.fatsecret.auth'), [
                    'telegram_id' => $user->telegram_id
                ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $authUrl = $responseData['authorization_url'] ?? null;

                    if ($authUrl) {
                        $keyboard = InlineKeyboardMarkup::make()
                            ->addRow(
                                InlineKeyboardButton::make('🔗 Открыть FatSecret', url: $authUrl)
                            )
                            ->addRow(
                                InlineKeyboardButton::make('🔄 Проверить подключение', callback_data: 'sync_start'),
                                InlineKeyboardButton::make('🔙 Назад', callback_data: 'sync_start')
                            );

                        $text = "🔗 *Подключение FatSecret*\n\n";
                        $text .= "✅ Ссылка для авторизации создана!\n\n";
                        $text .= "1️⃣ Нажмите кнопку \"🔗 Открыть FatSecret\"\n";
                        $text .= "2️⃣ Войдите в свой аккаунт FatSecret\n";
                        $text .= "3️⃣ Разрешите доступ к данным\n";
                        $text .= "4️⃣ Вернитесь в бот и проверьте подключение\n\n";
                        $text .= "⏰ Ссылка действительна в течение 15 минут";

                        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
                    } else {
                        $bot->answerCallbackQuery('❌ Ошибка создания ссылки авторизации');
                    }
                } else {
                    $bot->answerCallbackQuery('❌ Ошибка сервера при создании авторизации');
                    Log::error('Failed to initiate Telegram FatSecret OAuth', [
                        'response' => $response->body(),
                        'status' => $response->status(),
                        'user_id' => $user->id
                    ]);
                }
            } catch (\Exception $e) {
                $bot->answerCallbackQuery('❌ Техническая ошибка');
                Log::error('Exception during Telegram FatSecret OAuth initiation', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
            }
        }
    }

    public function executeSyncWithDateRange(Nutgram $bot, string $syncType): void
    {
        Log::info('executeSyncWithDateRange called', ['sync_type' => $syncType, 'user_id' => $bot->userId()]);
        
        $user = $this->userService->getCurrentUser($bot);
        Log::info('User retrieved', ['user_found' => $user ? 'yes' : 'no', 'user_id' => $user?->id]);
        
        if (!$user || !$user->isFatSecretAuthorized()) {
            Log::warning('User not found or not authorized', ['user_found' => $user ? 'yes' : 'no', 'authorized' => $user?->isFatSecretAuthorized()]);
            $bot->answerCallbackQuery('FatSecret не подключен');
            return;
        }

        // First check if we have a selected date from the date selection process
        $selectedDate = $this->dateService->getUserSelectedDate($bot->userId());
        Log::info('Selected date retrieved from cache', ['selected_date' => $selectedDate]);
        
        if ($selectedDate && isset($selectedDate['context']) && str_contains($selectedDate['context'], $syncType)) {
            Log::info('Using selected date for sync', ['selected_date' => $selectedDate, 'sync_type' => $syncType]);
            
            // User has selected a specific date, use it
            if (isset($selectedDate['is_range'])) {
                $startDate = Carbon::parse($selectedDate['start_date']);
                $endDate = Carbon::parse($selectedDate['end_date']);
            } else {
                $startDate = $endDate = Carbon::parse($selectedDate['date']);
            }
            
            Log::info('Calling performSync with selected date', ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')]);
            $this->performSync($bot, $user, $syncType, $startDate, $endDate);
            return;
        }

        // If no selected date, try default date ranges
        $dateRange = $this->getDefaultDateRange($syncType);
        Log::info('No selected date, trying default range', ['sync_type' => $syncType, 'date_range' => $dateRange]);
        
        if (!$dateRange) {
            Log::info('No default range, showing date selection', ['sync_type' => $syncType]);
            $this->dateService->showDateSelection($bot, "sync_{$syncType}");
            return;
        }

        Log::info('Calling performSync with default range', ['start' => $dateRange['start']->format('Y-m-d'), 'end' => $dateRange['end']->format('Y-m-d')]);
        $this->performSync($bot, $user, $syncType, $dateRange['start'], $dateRange['end']);
    }

    public function handleDateRangeSync(Nutgram $bot, string $data): void
    {
        $selectedDate = $this->dateService->getUserSelectedDate($bot->userId());
        if (!$selectedDate) {
            $this->dateService->showDateSelection($bot, 'sync');
            return;
        }

        if (isset($selectedDate['is_range'])) {
            $startDate = Carbon::parse($selectedDate['start_date']);
            $endDate = Carbon::parse($selectedDate['end_date']);
        } else {
            $startDate = $endDate = Carbon::parse($selectedDate['date']);
        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('🔄 Все данные', callback_data: 'sync_range_all'),
                InlineKeyboardButton::make('⚖️ Только вес', callback_data: 'sync_range_weight')
            )
            ->addRow(
                InlineKeyboardButton::make('🍎 Только питание', callback_data: 'sync_range_food'),
                InlineKeyboardButton::make('📅 Изменить даты', callback_data: 'sync_daterange')
            );

        $text = "🔄 *Синхронизация за период*\n\n";
        if ($startDate->equalTo($endDate)) {
            $text .= "📅 Дата: {$startDate->format('d.m.Y')}\n\n";
        } else {
            $text .= "📅 С: {$startDate->format('d.m.Y')}\n";
            $text .= "📅 По: {$endDate->format('d.m.Y')}\n\n";
        }
        $text .= "Выберите тип данных для синхронизации:";

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function performSync(Nutgram $bot, User $user, string $syncType, Carbon $startDate, Carbon $endDate): void
    {
        $authToken = new OAuthTokenDto($user->oauth_token, $user->oauth_token_secret);

        $progressMessage = $bot->sendMessage('🔄 *Начинаем синхронизацию...*', parse_mode: 'Markdown');
        $messageId = $progressMessage->message_id;

        $results = [
            'weight' => ['success' => 0, 'errors' => 0],
            'food' => ['success' => 0, 'errors' => 0]
        ];

        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $processedDays = 0;

        while ($current <= $endDate) {
            $processedDays++;

            // Update progress
            $progress = round(($processedDays / $totalDays) * 100);
            $bot->editMessageText(
                "🔄 *Синхронизация: {$progress}%*\n\nОбрабатываем: {$current->format('d.m.Y')}",
                message_id: $messageId,
                parse_mode: 'Markdown'
            );

            if (in_array($syncType, ['all', 'weight'])) {
                try {
                    Log::info('Trying to get weight data', ['user_id' => $user->id, 'date' => $current->format('Y-m-d')]);
                    $weightDto = $this->fatSecretService->getWeightByDate($authToken, $current);
                    Log::info('Weight data retrieved successfully', ['weight' => $weightDto->getWeight(), 'unit' => $weightDto->getUnit()]);
                    $this->saveWeightData($user, $current, $weightDto);
                    $results['weight']['success']++;
                    Log::info('Weight save completed', ['success_count' => $results['weight']['success']]);
                } catch (RecordNotFoundException $e) {
                    Log::info('No weight data found for date', ['user_id' => $user->id, 'date' => $current->format('Y-m-d')]);
                    // No data for this date, not an error
                } catch (\Exception $e) {
                    $results['weight']['errors']++;
                    Log::error('FatSecret weight sync error', [
                        'user_id' => $user->id,
                        'date' => $current->format('Y-m-d'),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (in_array($syncType, ['all', 'food'])) {
                try {
                    $foodDto = $this->fatSecretService->getFoodEntryByDate($authToken, $current);
                    $this->saveFoodData($user, $current, $foodDto);
                    $results['food']['success']++;
                } catch (RecordNotFoundException) {
                    // No data for this date, not an error
                } catch (\Exception $e) {
                    $results['food']['errors']++;
                    Log::error('FatSecret food sync error', [
                        'user_id' => $user->id,
                        'date' => $current->format('Y-m-d'),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $current->addDay();
            usleep(500000); // 0.5 second delay to avoid API limits
        }

        Log::info('Sync processing completed', ['results' => $results, 'sync_type' => $syncType]);
        $this->showSyncResults($bot, $messageId, $results, $syncType, $startDate, $endDate);
        $this->updateLastSyncTimestamp($user->id);
        Log::info('Sync fully completed and timestamp updated');
    }

    private function showSyncResults(Nutgram $bot, int $messageId, array $results, string $syncType, Carbon $startDate, Carbon $endDate): void
    {
        $text = "✅ *Синхронизация завершена!*\n\n";

        if ($startDate->equalTo($endDate)) {
            $text .= "📅 Дата: {$startDate->format('d.m.Y')}\n\n";
        } else {
            $text .= "📅 Период: {$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')}\n\n";
        }

        if (in_array($syncType, ['all', 'weight'])) {
            $text .= "⚖️ *Вес:*\n";
            $text .= "✅ Успешно: {$results['weight']['success']}\n";
            if ($results['weight']['errors'] > 0) {
                $text .= "❌ Ошибки: {$results['weight']['errors']}\n";
            }
            $text .= "\n";
        }

        if (in_array($syncType, ['all', 'food'])) {
            $text .= "🍎 *Питание:*\n";
            $text .= "✅ Успешно: {$results['food']['success']}\n";
            if ($results['food']['errors'] > 0) {
                $text .= "❌ Ошибки: {$results['food']['errors']}\n";
            }
            $text .= "\n";
        }

        $text .= "🕒 Завершено: " . Carbon::now()->format('H:i');

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('🔄 Синхронизировать еще', callback_data: 'sync_start'),
                InlineKeyboardButton::make('🔙 Главное меню', callback_data: 'start')
            );

        $bot->editMessageText($text, message_id: $messageId, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function getDefaultDateRange(string $syncType): ?array
    {
        return match($syncType) {
            'since_last' => $this->getSinceLast(),
            'today' => ['start' => Carbon::now(), 'end' => Carbon::now()],
            'yesterday' => ['start' => Carbon::yesterday(), 'end' => Carbon::yesterday()],
            'week' => ['start' => Carbon::now()->subDays(6), 'end' => Carbon::now()],
            default => null
        };
    }

    private function getSinceLast(): ?array
    {
        $lastSync = $this->getLastSyncTimestamp(auth()->id());
        if (!$lastSync) {
            return ['start' => Carbon::now()->subDays(7), 'end' => Carbon::now()];
        }

        return [
            'start' => Carbon::parse($lastSync)->addDay(),
            'end' => Carbon::now()
        ];
    }

    private function saveWeightData(User $user, Carbon $date, WeightDto $weightDto): void
    {
        try {
            // Create DTO for the weight store action
            $diaryWeightDto = new DiaryWeightStoreDto(
                date: $date->format('Y-m-d'),
                weight: $weightDto->getWeight(),
                unit: $weightDto->getUnit()
            );

            // Temporarily authenticate as the user to save the weight data
            $originalUser = Auth::user();
            Auth::login($user);

            // Store weight using existing action
            ($this->storeWeightAction)($diaryWeightDto);

            // Restore original authentication
            if ($originalUser) {
                Auth::login($originalUser);
            } else {
                Auth::logout();
            }

            Log::info('Weight data saved successfully via Telegram sync', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'weight' => $weightDto->getWeight(),
                'unit' => $weightDto->getUnit()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save weight data via Telegram sync', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function saveFoodData(User $user, Carbon $date, FoodEntryDto $foodDto): void
    {
        try {
            // Convert FatSecret FoodEntryDto to UserFoodEntryDto using the factory
            $userFoodEntryDto = $this->dtoFactory->createUserFoodEntryFromFoodEntry(
                $user->id,
                $date,
                $foodDto
            );

            // Create DTO for the macros store action  
            $diaryMacrosDto = new DiaryMacrosStoreDto(
                date: $date->format('Y-m-d'),
                kcal: (float) $userFoodEntryDto->getCalories(),
                protein: $userFoodEntryDto->getProtein(),
                fat: $userFoodEntryDto->getFat(),
                carbs: $userFoodEntryDto->getCarbohydrate()
            );

            // Temporarily authenticate as the user to save the food data
            $originalUser = Auth::user();
            Auth::login($user);

            // Store macros using existing action
            ($this->storeMacrosAction)($diaryMacrosDto);

            // Restore original authentication
            if ($originalUser) {
                Auth::login($originalUser);
            } else {
                Auth::logout();
            }

            Log::info('Food data saved successfully via Telegram sync', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'calories' => $userFoodEntryDto->getCalories(),
                'protein' => $userFoodEntryDto->getProtein(),
                'fat' => $userFoodEntryDto->getFat(),
                'carbohydrate' => $userFoodEntryDto->getCarbohydrate()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save food data via Telegram sync', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disconnectFatSecret(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);
        if (!$user) {
            $bot->answerCallbackQuery('Ошибка авторизации');
            return;
        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Да, отключить', callback_data: 'sync_disconnect_confirm'),
                InlineKeyboardButton::make('❌ Отмена', callback_data: 'sync_start')
            );

        $text = "❌ *Отключение FatSecret*\n\n";
        $text .= "Вы уверены, что хотите отключить FatSecret?\n\n";
        $text .= "⚠️ После отключения синхронизация данных будет невозможна до повторного подключения.";

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function getLastSyncTimestamp(int $userId): ?string
    {
        $key = "fatsecret_last_sync_{$userId}";
        return Cache::get($key);
    }

    private function updateLastSyncTimestamp(int $userId): void
    {
        $key = "fatsecret_last_sync_{$userId}";
        Cache::put($key, Carbon::now()->toDateTimeString(), now()->addMonths(3));
    }
}
