<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class SyncMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $instructionsText = "🔄 **Синхронизация с FatSecret**\n\n" .
                           "Выберите тип синхронизации:\n\n" .
                           "💡 **Доступные опции:**\n" .
                           "🔄 **Полная** - Синхронизация всех данных\n" .
                           "⚖️ **Вес** - Только данные о весе\n" .
                           "🍎 **Дневник питания** - Только питание\n\n" .
                           "⚠️ **Требуется подключение к FatSecret**";

        $this->menuText($instructionsText)
            ->addButtonRow(
                InlineKeyboardButton::make('🔄 Полная синхронизация', callback_data: CallbackData::SYNC_FULL . '@selectSyncType')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('⚖️ Синхронизация веса', callback_data: CallbackData::SYNC_WEIGHT . '@selectSyncType')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🍎 Дневник питания', callback_data: CallbackData::SYNC_FOOD . '@selectSyncType')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function selectSyncType(Nutgram $bot)
    {
        $callbackData = $bot->callbackQuery()->data;

        // Check FatSecret connection (this would normally check database)
        $isFatSecretConnected = false; // Replace with actual check

        if (!$isFatSecretConnected) {
            $this->menuText('❌ **FatSecret не подключен**\n\n' .
                           'Для синхронизации необходимо сначала подключить FatSecret в настройках.')
                ->addButtonRow(
                    InlineKeyboardButton::make('⚙️ Настройки', callback_data: CallbackData::SETTINGS . '@goToSettings')
                )
                ->addButtonRow(
                    InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
                )
                ->showMenu();
            return;
        }

        // Extract sync type from callback data
        $syncType = match($callbackData) {
            CallbackData::SYNC_FULL => 'полная синхронизация',
            CallbackData::SYNC_WEIGHT => 'синхронизация веса',
            CallbackData::SYNC_FOOD => 'синхронизация дневника питания',
            default => 'неизвестный тип'
        };

        // Store sync type in user session/cache
        cache()->put("telegram_user_sync_type_{$bot->userId()}", $syncType, 900);
        cache()->put("telegram_user_sync_callback_{$bot->userId()}", $callbackData, 900);

        // Start sync conversation
        $bot->startConversation(new \App\Telegram\Conversations\SyncConversation());

        $this->end();
    }

    public function goToSettings(Nutgram $bot)
    {
        $this->end();
        SettingsMenu::begin($bot);
    }

    public function backToMain(Nutgram $bot)
    {
        $this->end();
        MainMenu::begin($bot);
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Используйте кнопки меню для выбора типа синхронизации.');
        $this->start($bot);
    }
}
