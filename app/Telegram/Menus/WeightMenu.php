<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class WeightMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $instructionsText = "⚖️ **Запись веса**\n\n" .
                           "💡 **Инструкции:**\n" .
                           "1. Введите ваш вес в килограммах\n" .
                           "2. Укажите дату измерения\n" .
                           "3. Подтвердите данные\n\n" .
                           "📊 **Формат веса:** 70.5 или 70,5\n" .
                           "📅 **Формат даты:** ДД.ММ.ГГГГ или ДД/ММ/ГГГГ\n\n" .
                           "Нажмите кнопку ниже для начала записи:";

        $this->menuText($instructionsText)
            ->addButtonRow(
                InlineKeyboardButton::make('⚖️ Записать вес', callback_data: CallbackData::WEIGHT_START . '@startWeightEntry')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function startWeightEntry(Nutgram $bot)
    {
        $this->end();
        $bot->startConversation(new \App\Telegram\Conversations\WeightConversation());
    }

    public function backToMain(Nutgram $bot)
    {
        $this->end();
        MainMenu::begin($bot);
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Используйте кнопку для записи веса.');
        $this->start($bot);
    }
}
