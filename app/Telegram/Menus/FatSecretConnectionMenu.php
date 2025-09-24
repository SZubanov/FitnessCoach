<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use App\Telegram\Services\TelegramFatSecretService;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class FatSecretConnectionMenu extends InlineMenu
{
    public function __construct(
        readonly protected TelegramFatSecretService $fatSecretService
    ) {
        parent::__construct();
    }

    public function start(Nutgram $bot)
    {
        $this->menuText('🔗 Привязка FatSecret')
            ->addButtonRow(
                InlineKeyboardButton::make('✅ Проверить привязку', callback_data: CallbackData::LINK_CHECK . '@checkConnection')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔗 Подключить FatSecret', callback_data: CallbackData::FATSECRET_CONNECT . '@connectToFatSecret')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🚪 Выйти из FatSecret', callback_data: CallbackData::FATSECRET_LOGOUT . '@logoutFromFatSecret')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();

    }

    protected function checkConnection(Nutgram $bot)
    {
        $isAuthorized = $this->fatSecretService->isUserFatSecretAuthorized($bot->userId());

        $statusMessage = $isAuthorized
            ? "✅ **Статус привязки**\n\nВаш аккаунт авторизован в FatSecret"
            : "❌ **Статус привязки**\n\nВаш аккаунт не авторизован в FatSecret\n\n";

        $this->menuText($statusMessage)
            ->clearButtons()
            ->addButtonRow(
                InlineKeyboardButton::make('↩️ Назад', callback_data: 'back_to_account@backToFatSecretMenu')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->showMenu();
    }

    public function connectToFatSecret(Nutgram $bot)
    {
        $oauthUrl = $this->fatSecretService->getRequestTokenUrl();
        $message = "🔗 **Подключение FatSecret**\n\n" .
            "Для подключения к FatSecret нажмите на ссылку ниже:\n\n" .
            "[Подключить FatSecret]({$oauthUrl})\n\n" .
            "После авторизации вы будете автоматически перенаправлены обратно в бот";

        $this->menuText($message)
            ->clearButtons()
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
        $this->fatSecretService->logout($bot->userId());

        $message = "🚪 **Отключение от FatSecret**\n\n" .
            "Ваш аккаунт был отключен от FatSecret\n\n" .
            "❌ Функции синхронизации больше не доступны\n";

        $bot->sendMessage($message, parse_mode: ParseMode::MARKDOWN);

        $this->end();

        MainMenu::begin($bot);
    }

    public function backToFatSecretMenu(Nutgram $bot)
    {
        $this->clearButtons();
        $this->start($bot);
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
