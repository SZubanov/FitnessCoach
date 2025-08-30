<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use App\Telegram\Constants\MenuIdentifiers;
use SergiX44\Nutgram\InlineMenu\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class FatSecretConnectionMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        // Check FatSecret connection status (this would normally check database)
        $isConnected = false; // Replace with actual check
        
        if ($isConnected) {
            $this->showConnectedMenu($bot);
        } else {
            $this->showDisconnectedMenu($bot);
        }
    }

    protected function showConnectedMenu(Nutgram $bot)
    {
        $this->menuText('🔐 FatSecret подключен\n\n✅ Ваш аккаунт подключен к FatSecret')
            ->addButtonRow(
                InlineKeyboardButton::make('🚪 Выйти из FatSecret', callback_data: CallbackData::FATSECRET_LOGOUT . '@logoutFromFatSecret')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_settings@backToSettings')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    protected function showDisconnectedMenu(Nutgram $bot)
    {
        $this->menuText('🔐 FatSecret не подключен\n\n❌ Для использования функций синхронизации необходимо подключить FatSecret')
            ->addButtonRow(
                InlineKeyboardButton::make('🔗 Подключить FatSecret', callback_data: CallbackData::FATSECRET_CONNECT . '@connectToFatSecret')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_settings@backToSettings')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function connectToFatSecret(Nutgram $bot)
    {
        // Generate OAuth URL and redirect user
        $oauthUrl = "https://www.fatsecret.com/oauth/authorize"; // Replace with actual OAuth URL
        
        $message = "🔗 **Подключение FatSecret**\n\n" .
                   "Для подключения к FatSecret нажмите на ссылку ниже:\n\n" .
                   "[Подключить FatSecret]({$oauthUrl})\n\n" .
                   "После авторизации вы будете автоматически перенаправлены обратно в бот";

        $this->menuText($message)
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_fatsecret@backToFatSecretMenu')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function logoutFromFatSecret(Nutgram $bot)
    {
        // Disconnect from FatSecret (this would normally update database)
        
        $message = "🚪 **Отключение от FatSecret**\n\n" .
                   "Ваш аккаунт был отключен от FatSecret\n\n" .
                   "❌ Функции синхронизации больше не доступны\n" .
                   "🔗 Для повторного подключения используйте кнопку \"Подключить FatSecret\"";

        $this->menuText($message)
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_fatsecret@backToFatSecretMenu')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function backToFatSecretMenu(Nutgram $bot)
    {
        $this->start($bot);
    }

    public function backToSettings(Nutgram $bot)
    {
        app(SettingsMenu::class)->start($bot);
        $this->end();
    }

    public function backToMain(Nutgram $bot)
    {
        app(MainMenu::class)->start($bot);
        $this->end();
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Используйте кнопки меню для навигации.');
        $this->start($bot);
    }
}