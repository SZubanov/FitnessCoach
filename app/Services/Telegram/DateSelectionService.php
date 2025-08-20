<?php

namespace App\Services\Telegram;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DateSelectionService
{
    public function showDateSelection(Nutgram $bot, string $context): void
    {
        $keyboard = $this->buildQuickDateKeyboard($context);
        
        $text = "📅 *Выберите дату:*\n\n";
        $text .= "Выберите быстрый вариант или откройте календарь для точной даты:";

        $bot->sendMessage($text, reply_markup: $keyboard, parse_mode: 'Markdown');
        
        $this->setUserState($bot->userId(), 'selecting_date', ['context' => $context]);
    }

    public function buildQuickDateKeyboard(string $context): InlineKeyboardMarkup
    {
        $today = Carbon::now();
        
        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('📅 Сегодня (' . $today->format('d.m') . ')', 
                    callback_data: "date_select_quick_today_{$context}")
            )
            ->addRow(
                InlineKeyboardButton::make('📅 Вчера (' . $today->subDay()->format('d.m') . ')', 
                    callback_data: "date_select_quick_yesterday_{$context}"),
                InlineKeyboardButton::make('📅 2 дня назад (' . $today->subDay()->format('d.m') . ')', 
                    callback_data: "date_select_quick_2days_{$context}")
            )
            ->addRow(
                InlineKeyboardButton::make('📊 Последние 7 дней', 
                    callback_data: "date_select_week_{$context}"),
                InlineKeyboardButton::make('🗓 Календарь', 
                    callback_data: "date_select_calendar_{$context}")
            )
            ->addRow(
                InlineKeyboardButton::make('❌ Отмена', callback_data: 'cancel')
            );
    }

    public function showCalendar(Nutgram $bot, string $context, ?Carbon $month = null): void
    {
        $month = $month ?? Carbon::now();
        $keyboard = $this->buildCalendarKeyboard($context, $month);
        
        $text = "🗓 *Календарь - {$month->translatedFormat('F Y')}*\n\n";
        $text .= "Выберите дату:";

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function buildCalendarKeyboard(string $context, Carbon $month): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();
        
        $firstDay = $month->copy()->startOfMonth();
        $lastDay = $month->copy()->endOfMonth();
        $today = Carbon::now();
        
        $keyboard->addRow(
            InlineKeyboardButton::make('◀️', 
                callback_data: "date_select_calendar_prev_{$context}_{$month->format('Y-m')}"),
            InlineKeyboardButton::make($month->translatedFormat('F Y'), callback_data: 'noop'),
            InlineKeyboardButton::make('▶️', 
                callback_data: "date_select_calendar_next_{$context}_{$month->format('Y-m')}")
        );
        
        $keyboard->addRow(
            InlineKeyboardButton::make('Пн', callback_data: 'noop'),
            InlineKeyboardButton::make('Вт', callback_data: 'noop'),
            InlineKeyboardButton::make('Ср', callback_data: 'noop'),
            InlineKeyboardButton::make('Чт', callback_data: 'noop'),
            InlineKeyboardButton::make('Пт', callback_data: 'noop'),
            InlineKeyboardButton::make('Сб', callback_data: 'noop'),
            InlineKeyboardButton::make('Вс', callback_data: 'noop')
        );

        $startOfWeek = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $lastDay->copy()->endOfWeek(Carbon::SUNDAY);

        $current = $startOfWeek->copy();
        while ($current <= $endOfWeek) {
            $row = [];
            for ($i = 0; $i < 7; $i++) {
                if ($current->month === $month->month && $current <= $today) {
                    $dayText = $current->day;
                    if ($current->isToday()) {
                        $dayText = "[$dayText]";
                    }
                    $row[] = InlineKeyboardButton::make($dayText, 
                        callback_data: "date_select_date_{$context}_{$current->format('Y-m-d')}");
                } else {
                    $row[] = InlineKeyboardButton::make(' ', callback_data: 'noop');
                }
                $current->addDay();
            }
            $keyboard->addRow(...$row);
        }

        $keyboard->addRow(
            InlineKeyboardButton::make('📅 Сегодня', 
                callback_data: "date_select_quick_today_{$context}"),
            InlineKeyboardButton::make('❌ Отмена', callback_data: 'cancel')
        );

        return $keyboard;
    }

    public function handleDateSelection(Nutgram $bot, string $data): void
    {
        $parts = explode('_', $data);
        $action = $parts[0] ?? '';
        
        // Extract context - for callbacks like "quick_today_sync_weight", 
        // context should be "sync_weight", not just "weight"
        if ($action === 'quick' && count($parts) >= 4) {
            // quick_today_sync_weight -> sync_weight
            $context = implode('_', array_slice($parts, 2));
        } elseif ($action === 'week' && count($parts) >= 3) {
            // week_sync_weight -> sync_weight  
            $context = implode('_', array_slice($parts, 1));
        } elseif ($action === 'calendar' && count($parts) >= 3) {
            // calendar_sync_weight or calendar_prev_sync_weight_2025-08
            if (in_array($parts[1], ['prev', 'next'])) {
                // calendar_prev_sync_weight_2025-08 -> sync_weight
                $context = implode('_', array_slice($parts, 2, -1));
            } else {
                // calendar_sync_weight -> sync_weight
                $context = implode('_', array_slice($parts, 1));
            }
        } else {
            // Fallback to last part for simple contexts
            $context = end($parts);
        }

        switch ($action) {
            case 'quick':
                $this->handleQuickDateSelection($bot, $parts, $context);
                break;
            case 'calendar':
                $this->handleCalendarAction($bot, $parts, $context);
                break;
            case 'date':
                $this->handleSpecificDateSelection($bot, $parts, $context);
                break;
            case 'week':
                $this->handleWeekSelection($bot, $context);
                break;
        }
    }

    private function handleQuickDateSelection(Nutgram $bot, array $parts, string $context): void
    {
        $dateType = $parts[1] ?? '';
        $selectedDate = match($dateType) {
            'today' => Carbon::now(),
            'yesterday' => Carbon::now()->subDay(),
            '2days' => Carbon::now()->subDays(2),
            default => Carbon::now()
        };

        $this->confirmDateSelection($bot, $selectedDate, $context);
    }

    private function handleSpecificDateSelection(Nutgram $bot, array $parts, string $context): void
    {
        $dateStr = $parts[2] ?? '';
        $selectedDate = Carbon::parse($dateStr);
        
        $this->confirmDateSelection($bot, $selectedDate, $context);
    }

    private function handleWeekSelection(Nutgram $bot, string $context): void
    {
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();
        
        $this->confirmDateRangeSelection($bot, $startDate, $endDate, $context);
    }

    private function confirmDateSelection(Nutgram $bot, Carbon $date, string $context): void
    {
        $this->setUserSelectedDate($bot->userId(), $date, $context);
        
        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Использовать эту дату', 
                    callback_data: "date_confirmed_{$context}"),
                InlineKeyboardButton::make('🔄 Изменить дату', 
                    callback_data: "date_change_{$context}")
            );

        $relativeDate = $this->getRelativeDateString($date);
        $text = "📅 *Выбрана дата:*\n\n";
        $text .= "🗓 {$date->format('d.m.Y')} ({$relativeDate})\n\n";
        $text .= "Подтвердите выбор или измените дату:";

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
    }

    private function confirmDateRangeSelection(Nutgram $bot, Carbon $startDate, Carbon $endDate, string $context): void
    {
        $this->setUserSelectedDateRange($bot->userId(), $startDate, $endDate, $context);
        
        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Использовать период', 
                    callback_data: "daterange_confirmed_{$context}"),
                InlineKeyboardButton::make('🔄 Изменить период', 
                    callback_data: "date_change_{$context}")
            );

        $text = "📅 *Выбран период:*\n\n";
        $text .= "📅 С: {$startDate->format('d.m.Y')}\n";
        $text .= "📅 По: {$endDate->format('d.m.Y')}\n\n";
        $text .= "Подтвердите выбор или измените период:";

        $bot->editMessageText($text, reply_markup: $keyboard, parse_mode: 'Markdown');
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

    private function setUserSelectedDate(int $userId, Carbon $date, string $context): void
    {
        $key = "telegram_user_selected_date_{$userId}";
        Cache::put($key, [
            'date' => $date->format('Y-m-d'),
            'context' => $context
        ], now()->addDay());
    }

    private function setUserSelectedDateRange(int $userId, Carbon $startDate, Carbon $endDate, string $context): void
    {
        $key = "telegram_user_selected_date_{$userId}";
        Cache::put($key, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'context' => $context,
            'is_range' => true
        ], now()->addDay());
    }

    public function getUserSelectedDate(int $userId): ?array
    {
        $key = "telegram_user_selected_date_{$userId}";
        return Cache::get($key);
    }

    public function clearUserState(int $userId): void
    {
        Cache::forget("telegram_user_state_{$userId}");
        Cache::forget("telegram_user_selected_date_{$userId}");
    }

    private function handleCalendarAction(Nutgram $bot, array $parts, string $context): void
    {
        $action = $parts[1] ?? '';
        
        switch ($action) {
            case 'prev':
            case 'next':
                $monthStr = $parts[3] ?? Carbon::now()->format('Y-m');
                $month = Carbon::parse($monthStr);
                if ($action === 'prev') {
                    $month->subMonth();
                } else {
                    $month->addMonth();
                }
                $this->showCalendar($bot, $context, $month);
                break;
            default:
                $this->showCalendar($bot, $context);
                break;
        }
    }
}