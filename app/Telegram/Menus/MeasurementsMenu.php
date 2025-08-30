<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class MeasurementsMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $instructionsText = "📏 **Замеры тела**\n\n" .
                           "Выберите часть тела для записи измерения:\n\n" .
                           "💡 **Инструкции:**\n" .
                           "1. Выберите часть тела\n" .
                           "2. Введите значение в сантиметрах\n" .
                           "3. Укажите дату в формате ДД.ММ.ГГГГ\n" .
                           "4. Подтвердите данные";

        $this->menuText($instructionsText)
            ->addButtonRow(
                InlineKeyboardButton::make('💪 Грудь', callback_data: CallbackData::MEASUREMENT_CHEST . '@selectMeasurement'),
                InlineKeyboardButton::make('🦴 Талия', callback_data: CallbackData::MEASUREMENT_WAIST . '@selectMeasurement')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🦒 Шея', callback_data: CallbackData::MEASUREMENT_NECK . '@selectMeasurement'),
                InlineKeyboardButton::make('💪 Бицепс', callback_data: CallbackData::MEASUREMENT_BICEPS . '@selectMeasurement')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🦴 Таз', callback_data: CallbackData::MEASUREMENT_PELVIS . '@selectMeasurement'),
                InlineKeyboardButton::make('🦵 Бедро', callback_data: CallbackData::MEASUREMENT_THIGH . '@selectMeasurement')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function selectMeasurement(Nutgram $bot)
    {
        $callbackData = $bot->callbackQuery()->data;

        // Extract measurement type from callback data
        $measurementType = match($callbackData) {
            CallbackData::MEASUREMENT_CHEST => 'грудь',
            CallbackData::MEASUREMENT_WAIST => 'талия',
            CallbackData::MEASUREMENT_NECK => 'шея',
            CallbackData::MEASUREMENT_BICEPS => 'бицепс',
            CallbackData::MEASUREMENT_PELVIS => 'таз',
            CallbackData::MEASUREMENT_THIGH => 'бедро',
            default => 'неизвестно'
        };

        // Store measurement type in user session/cache
        cache()->put("telegram_user_measurement_type_{$bot->userId()}", $measurementType, 900);

        // Start measurement conversation
        // This would normally start a Conversation class for input handling
        $bot->sendMessage(
            "📏 **Замер: {$measurementType}**\n\n" .
            "Введите значение измерения в сантиметрах:\n" .
            "Например: 95"
        );

        $this->end();
    }

    public function backToMain(Nutgram $bot)
    {
        app(MainMenu::class)->start($bot);
        $this->end();
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Используйте кнопки меню для выбора измерения.');
        $this->start($bot);
    }
}
