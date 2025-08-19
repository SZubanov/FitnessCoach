<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Models\UserSize;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class MeasurementService
{
    private array $measurementTypes = [
        'chest' => ['name' => 'Грудь', 'emoji' => '🫀', 'unit' => 'см'],
        'waist' => ['name' => 'Талия', 'emoji' => '⚡', 'unit' => 'см'], 
        'pelvis' => ['name' => 'Бедра', 'emoji' => '🍑', 'unit' => 'см'],
        'biceps' => ['name' => 'Бицепс', 'emoji' => '💪', 'unit' => 'см'],
        'thigh' => ['name' => 'Бедро', 'emoji' => '🦵', 'unit' => 'см'],
        'neck' => ['name' => 'Шея', 'emoji' => '🗣️', 'unit' => 'см'],
        'tibia' => ['name' => 'Голень', 'emoji' => '🦵', 'unit' => 'см']
    ];

    public function __construct(
        private DateSelectionService $dateService,
        private TelegramUserService $userService
    ) {}

    public function handleMeasurementCallback(Nutgram $bot, string $data): void
    {
        $parts = explode('_', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'start':
                $this->showMeasurementMenu($bot);
                break;
            case 'type':
                $measurementType = $parts[1] ?? '';
                $this->showMeasurementInput($bot, $measurementType);
                break;
            case 'save':
                $measurementType = $parts[1] ?? '';
                $value = $parts[2] ?? '';
                $this->saveMeasurement($bot, $measurementType, $value);
                break;
            case 'view':
                $this->showMeasurements($bot);
                break;
        }
    }

    public function showMeasurementMenu(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);
        if (!$user) {
            $bot->sendMessage('❌ Ошибка авторизации');
            return;
        }

        $selectedDate = $this->dateService->getUserSelectedDate($bot->userId());
        if (!$selectedDate) {
            $this->dateService->showDateSelection($bot, 'measurements');
            return;
        }

        $date = Carbon::parse($selectedDate['date']);
        $existingMeasurements = $this->getUserMeasurementsForDate($user->id, $date);

        $keyboard = InlineKeyboardMarkup::make();
        
        $keyboard->addRow(
            InlineKeyboardButton::make('👀 Просмотреть замеры', callback_data: 'measurement_view')
        );

        $types = array_keys($this->measurementTypes);
        for ($i = 0; $i < count($types); $i += 2) {
            $buttons = [];
            
            // First button in row
            $type = $types[$i];
            $info = $this->measurementTypes[$type];
            $currentValue = $existingMeasurements[$type] ?? null;
            $text = $info['emoji'] . ' ' . $info['name'];
            if ($currentValue) {
                $text .= " ({$currentValue} {$info['unit']})";
            }
            $buttons[] = InlineKeyboardButton::make($text, callback_data: "measurement_type_{$type}");
            
            // Second button in row (if exists)
            if (isset($types[$i + 1])) {
                $type = $types[$i + 1];
                $info = $this->measurementTypes[$type];
                $currentValue = $existingMeasurements[$type] ?? null;
                $text = $info['emoji'] . ' ' . $info['name'];
                if ($currentValue) {
                    $text .= " ({$currentValue} {$info['unit']})";
                }
                $buttons[] = InlineKeyboardButton::make($text, callback_data: "measurement_type_{$type}");
            }
            
            $keyboard->addRow(...$buttons);
        }

        $keyboard->addRow(
            InlineKeyboardButton::make('📅 Изменить дату', callback_data: 'date_change_measurements'),
            InlineKeyboardButton::make('✅ Завершить', callback_data: 'measurements_complete')
        );

        $relativeDate = $this->getRelativeDateString($date);
        $text = "📏 *Замеры тела*\n\n";
        $text .= "📅 Дата: {$date->format('d.m.Y')} ({$relativeDate})\n\n";
        $text .= "Выберите тип замера для добавления или изменения:";

        if ($existingMeasurements) {
            $text .= "\n\n*Текущие замеры:*\n";
            foreach ($existingMeasurements as $type => $value) {
                if ($value && isset($this->measurementTypes[$type])) {
                    $info = $this->measurementTypes[$type];
                    $text .= "{$info['emoji']} {$info['name']}: {$value} {$info['unit']}\n";
                }
            }
        }

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    public function showMeasurementInput(Nutgram $bot, string $measurementType): void
    {
        if (!isset($this->measurementTypes[$measurementType])) {
            $bot->answerCallbackQuery('Неизвестный тип замера');
            return;
        }

        $user = $this->userService->getCurrentUser($bot);
        $selectedDate = $this->dateService->getUserSelectedDate($bot->userId());
        $date = Carbon::parse($selectedDate['date']);

        $info = $this->measurementTypes[$measurementType];
        $currentValue = $this->getUserMeasurementValue($user->id, $date, $measurementType);
        $recentValues = $this->getRecentMeasurementValues($user->id, $measurementType, $date);

        $keyboard = InlineKeyboardMarkup::make();

        if ($currentValue) {
            $keyboard->addRow(
                InlineKeyboardButton::make("Текущее: {$currentValue} {$info['unit']}", 
                    callback_data: "measurement_save_{$measurementType}_{$currentValue}")
            );
        }

        if (!empty($recentValues)) {
            $buttons = [];
            foreach (array_slice($recentValues, 0, 3) as $value) {
                if ($value != $currentValue) {
                    $buttons[] = InlineKeyboardButton::make("{$value}", 
                        callback_data: "measurement_save_{$measurementType}_{$value}");
                }
            }
            if ($buttons) {
                if (count($buttons) >= 2) {
                    $keyboard->addRow($buttons[0], $buttons[1]);
                    if (isset($buttons[2])) {
                        $keyboard->addRow($buttons[2]);
                    }
                } else {
                    $keyboard->addRow($buttons[0]);
                }
            }
        }

        $keyboard->addRow(
            InlineKeyboardButton::make('✏️ Ввести вручную', callback_data: "measurement_manual_{$measurementType}"),
            InlineKeyboardButton::make('🔙 Назад', callback_data: 'measurement_start')
        );

        $relativeDate = $this->getRelativeDateString($date);
        $text = "📏 *{$info['name']}* {$info['emoji']}\n\n";
        $text .= "📅 Дата: {$date->format('d.m.Y')} ({$relativeDate})\n\n";

        if ($currentValue) {
            $text .= "Текущее значение: *{$currentValue} {$info['unit']}*\n\n";
        }

        if (!empty($recentValues)) {
            $text .= "Недавние значения:\n";
            foreach ($recentValues as $value) {
                $text .= "• {$value} {$info['unit']}\n";
            }
            $text .= "\n";
        }

        $text .= "Выберите значение или введите новое:";

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');

        $this->setUserState($bot->userId(), 'measurement_input', [
            'type' => $measurementType,
            'date' => $selectedDate['date']
        ]);
    }

    public function handleManualInput(Nutgram $bot): void
    {
        $state = $this->getUserState($bot->userId());
        if (!$state || $state['state'] !== 'measurement_input') {
            return;
        }

        $text = $bot->message()?->text;
        if (!$text) {
            return;
        }

        $measurementType = $state['data']['type'];
        $value = $this->parseValue($text);

        if (!$value || $value <= 0 || $value > 300) {
            $bot->sendMessage('❌ Неверное значение. Введите число от 1 до 300 см.');
            return;
        }

        $this->saveMeasurement($bot, $measurementType, $value);
    }

    public function saveMeasurement(Nutgram $bot, string $measurementType, float $value): void
    {
        $user = $this->userService->getCurrentUser($bot);
        $selectedDate = $this->dateService->getUserSelectedDate($bot->userId());
        $date = Carbon::parse($selectedDate['date']);

        if (!isset($this->measurementTypes[$measurementType])) {
            $bot->answerCallbackQuery('Неизвестный тип замера');
            return;
        }

        $existingSize = UserSize::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($existingSize) {
            $existingSize->update([$measurementType => $value]);
        } else {
            UserSize::create([
                'user_id' => $user->id,
                'date' => $date,
                $measurementType => $value
            ]);
        }

        $this->clearUserState($bot->userId());

        $info = $this->measurementTypes[$measurementType];
        $relativeDate = $this->getRelativeDateString($date);
        
        $text = "✅ *Замер сохранен!*\n\n";
        $text .= "{$info['emoji']} {$info['name']}: *{$value} {$info['unit']}*\n";
        $text .= "📅 Дата: {$date->format('d.m.Y')} ({$relativeDate})";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('➕ Добавить еще', callback_data: 'measurement_start'),
                InlineKeyboardButton::make('📅 Другая дата', callback_data: 'date_change_measurements')
            )
            ->addRow(
                InlineKeyboardButton::make('👀 Просмотреть прогресс', callback_data: 'measurement_view'),
                InlineKeyboardButton::make('✅ Завершить', callback_data: 'measurements_complete')
            );

        $bot->sendMessage($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    public function showMeasurements(Nutgram $bot): void
    {
        $user = $this->userService->getCurrentUser($bot);
        $selectedDate = $this->dateService->getUserSelectedDate($bot->userId());
        $date = Carbon::parse($selectedDate['date']);
        
        $measurements = $this->getUserMeasurementsForDate($user->id, $date);
        $recentMeasurements = $this->getRecentMeasurements($user->id, $date, 5);

        $relativeDate = $this->getRelativeDateString($date);
        $text = "📊 *Замеры тела*\n\n";
        $text .= "📅 {$date->format('d.m.Y')} ({$relativeDate})\n\n";

        if ($measurements) {
            foreach ($this->measurementTypes as $type => $info) {
                if (isset($measurements[$type]) && $measurements[$type]) {
                    $text .= "{$info['emoji']} {$info['name']}: *{$measurements[$type]} {$info['unit']}*\n";
                }
            }
        } else {
            $text .= "📝 На эту дату замеры не добавлены\n";
        }

        if ($recentMeasurements) {
            $text .= "\n📈 *Последние замеры:*\n";
            foreach ($recentMeasurements as $measurement) {
                $measurementDate = Carbon::parse($measurement['date']);
                $text .= "\n📅 {$measurementDate->format('d.m.Y')} ({$this->getRelativeDateString($measurementDate)}):\n";
                
                foreach ($this->measurementTypes as $type => $info) {
                    if (isset($measurement[$type]) && $measurement[$type]) {
                        $text .= "  {$info['emoji']} {$info['name']}: {$measurement[$type]} {$info['unit']}\n";
                    }
                }
            }
        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('➕ Добавить замер', callback_data: 'measurement_start'),
                InlineKeyboardButton::make('📅 Другая дата', callback_data: 'date_change_measurements')
            )
            ->addRow(
                InlineKeyboardButton::make('🔙 Главное меню', callback_data: 'start')
            );

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function getUserMeasurementsForDate(int $userId, Carbon $date): array
    {
        $measurement = UserSize::where('user_id', $userId)
            ->whereDate('date', $date)
            ->first();

        if (!$measurement) {
            return [];
        }

        return [
            'neck' => $measurement->neck,
            'chest' => $measurement->chest,
            'waist' => $measurement->waist,
            'biceps' => $measurement->biceps,
            'pelvis' => $measurement->pelvis,
            'thigh' => $measurement->thigh,
            'tibia' => $measurement->tibia,
        ];
    }

    private function getUserMeasurementValue(int $userId, Carbon $date, string $type): ?float
    {
        $measurement = UserSize::where('user_id', $userId)
            ->whereDate('date', $date)
            ->first();

        return $measurement?->{$type};
    }

    private function getRecentMeasurementValues(int $userId, string $type, Carbon $excludeDate, int $limit = 5): array
    {
        return UserSize::where('user_id', $userId)
            ->whereDate('date', '!=', $excludeDate)
            ->whereNotNull($type)
            ->orderBy('date', 'desc')
            ->take($limit)
            ->pluck($type)
            ->unique()
            ->values()
            ->toArray();
    }

    private function getRecentMeasurements(int $userId, Carbon $excludeDate, int $limit = 5): array
    {
        return UserSize::where('user_id', $userId)
            ->whereDate('date', '!=', $excludeDate)
            ->orderBy('date', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($measurement) {
                return [
                    'date' => $measurement->date,
                    'neck' => $measurement->neck,
                    'chest' => $measurement->chest,
                    'waist' => $measurement->waist,
                    'biceps' => $measurement->biceps,
                    'pelvis' => $measurement->pelvis,
                    'thigh' => $measurement->thigh,
                    'tibia' => $measurement->tibia,
                ];
            })
            ->toArray();
    }

    private function parseValue(string $text): ?float
    {
        $cleaned = preg_replace('/[^\d.,]/', '', $text);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function getRelativeDateString(Carbon $date): string
    {
        $today = Carbon::now();
        $diffInDays = $today->diffInDays($date);

        if ($date->isToday()) {
            return 'сегодня';
        } elseif ($date->isYesterday()) {
            return 'вчера';
        } elseif ($diffInDays <= 7 && $date->isPast()) {
            return $diffInDays . ' ' . str()->plural($diffInDays, ['день', 'дня', 'дней']) . ' назад';
        }

        return $date->translatedFormat('l');
    }

    private function setUserState(int $userId, string $state, array $data = []): void
    {
        $key = "telegram_user_state_{$userId}";
        Cache::put($key, ['state' => $state, 'data' => $data], now()->addHour());
    }

    private function getUserState(int $userId): ?array
    {
        $key = "telegram_user_state_{$userId}";
        return Cache::get($key);
    }

    private function clearUserState(int $userId): void
    {
        Cache::forget("telegram_user_state_{$userId}");
    }
}