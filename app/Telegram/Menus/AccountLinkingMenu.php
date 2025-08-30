<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class AccountLinkingMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $this->menuText('🔗 Привязка аккаунта')
            ->addButtonRow(
                InlineKeyboardButton::make('📝 Получить код для привязки', callback_data: CallbackData::LINK_NEW . '@generateLinkCode')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('✅ Проверить привязку', callback_data: CallbackData::LINK_CHECK . '@checkLinkStatus')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('❌ Отвязать аккаунт', callback_data: CallbackData::LINK_REMOVE . '@removeLinkAccount')
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

    public function generateLinkCode(Nutgram $bot)
    {
        // Generate a temporary link code
        $linkCode = strtoupper(substr(md5(time() . $bot->userId()), 0, 8));

        // Store the code in cache with 15-minute TTL
        cache()->put("telegram_link_code_{$linkCode}", $bot->userId(), 900);

        $message = "🔗 **Код для привязки аккаунта**\n\n" .
                   "Ваш код: `{$linkCode}`\n\n" .
                   "⏰ Код действителен 15 минут\n" .
                   "🌐 Используйте этот код в веб-интерфейсе для привязки аккаунта\n\n" .
                   "⚠️ **Внимание:** При создании нового кода, старый перестает действовать";

        $this->menuText($message)
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_account@backToAccountLinking')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function checkLinkStatus(Nutgram $bot)
    {
        // Check if user account is linked (this would normally check database)
        $isLinked = false; // Replace with actual check

        $statusMessage = $isLinked
            ? "✅ **Статус привязки**\n\nВаш аккаунт привязан к системе FitnessCoach"
            : "❌ **Статус привязки**\n\nВаш аккаунт не привязан к системе FitnessCoach\n\n" .
              "Используйте кнопку \"Получить код для привязки\" для создания кода";

        $this->menuText($statusMessage)
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_account@backToAccountLinking')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function removeLinkAccount(Nutgram $bot)
    {
        // Remove account linking (this would normally update database)

        $message = "❌ **Отвязка аккаунта**\n\n" .
                   "Ваш аккаунт был отвязан от системы FitnessCoach\n\n" .
                   "Для повторной привязки используйте функцию \"Получить код для привязки\"";

        $this->menuText($message)
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_account@backToAccountLinking')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function backToAccountLinking(Nutgram $bot)
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
