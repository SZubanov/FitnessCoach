<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class MainMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $this->menuText('🏠 Главное меню FitnessCoach')
            ->addButtonRow(
                InlineKeyboardButton::make('⚙️ Настройки', callback_data: CallbackData::SETTINGS . '@showSettings')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('📏 Замеры', callback_data: CallbackData::MEASUREMENTS_START . '@showMeasurements')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔄 Синхронизация', callback_data: CallbackData::SYNC_START . '@showSync')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🍎 КБЖУ', callback_data: CallbackData::MACROS_START . '@showMacros')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('⚖️ Вес', callback_data: CallbackData::WEIGHT_START . '@showWeight')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('❓ Помощь', callback_data: CallbackData::HELP . '@showHelp')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function showSettings(Nutgram $bot)
    {
        SettingsMenu::begin($bot);
    }

    public function showMeasurements(Nutgram $bot)
    {
        // Navigate to Measurements menu
        MeasurementsMenu::begin($bot);
    }

    public function showSync(Nutgram $bot)
    {
        SyncMenu::begin($bot);
    }

    public function showMacros(Nutgram $bot)
    {
        MacrosMenu::begin($bot);
    }

    public function showWeight(Nutgram $bot)
    {
        WeightMenu::begin($bot);
    }

    public function showHelp(Nutgram $bot)
    {
        $helpText = "📖 **Помощь FitnessCoach Bot**\n\n" .
                   "**Доступные функции:**\n" .
                   "📏 **Замеры** - Записывайте измерения тела\n" .
                   "🔄 **Синхронизация** - Синхронизация с FatSecret\n" .
                   "🍎 **КБЖУ** - Отслеживание калорий и макронутриентов\n" .
                   "⚖️ **Вес** - Записывайте показания веса\n" .
                   "⚙️ **Настройки** - Управление аккаунтом и подключениями\n\n" .
                   "**Формат даты:** DD.MM.YYYY или DD/MM/YYYY\n" .
                   "**Пример:** 25.12.2024 или 25/12/2024";

        $this->menuText($helpText)
            ->clearButtons()
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function backToMain(Nutgram $bot)
    {
        $this->start($bot);
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Используйте кнопки меню для навигации.');
        $this->start($bot);
    }
}
