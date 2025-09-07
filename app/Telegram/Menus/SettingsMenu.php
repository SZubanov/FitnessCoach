<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class SettingsMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $this->menuText('⚙️ Настройки')
            ->addButtonRow(
                InlineKeyboardButton::make('🔗 Привязка аккаунта', callback_data: 'link_menu@showAccountLinking')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔐 FatSecret', callback_data: 'fatsecret_menu@showFatSecretConnection')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function showAccountLinking(Nutgram $bot)
    {
        // Navigate to Account Linking menu
        AccountLinkingMenu::begin($bot);
//        $this->end();
    }

    public function showFatSecretConnection(Nutgram $bot)
    {
        // Navigate to FatSecret Connection menu
        FatSecretConnectionMenu::begin($bot);
//        $this->end();
    }

    public function backToMain(Nutgram $bot)
    {
        $this->end();
        MainMenu::begin($bot);
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Используйте кнопки меню для навигации.');
        $this->start($bot);
    }
}
