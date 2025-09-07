<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use App\Telegram\Services\TelegramAccountService;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class AccountLinkingMenu extends InlineMenu
{
    public function __construct(readonly protected TelegramAccountService $telegramAccountService)
    {
        parent::__construct();
    }

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
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function generateLinkCode(Nutgram $bot)
    {
        // Generate a temporary link code
        $linkCode = $this->telegramAccountService->generateLinkAccountCode($bot->userId());

        $message = "🔗 **Код для привязки аккаунта**\n\n" .
            "Ваш код: `{$linkCode}`\n\n" .
            "⏰ Код действителен 15 минут\n" .
            "🌐 Используйте этот код в веб-интерфейсе для привязки аккаунта\n\n" .
            "⚠️ **Внимание:** При создании нового кода, старый перестает действовать";

        $this->clearButtons()
            ->menuText($message)
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
        $isLinked = $this->telegramAccountService->checkLinkAccountStatus($bot->userId());

        $statusMessage = $isLinked
            ? "✅ **Статус привязки**\n\nВаш аккаунт привязан к системе FitnessCoach"
            : "❌ **Статус привязки**\n\nВаш аккаунт не привязан к системе FitnessCoach\n\n" .
            "Используйте кнопку \"Получить код для привязки\" для создания кода";

        $this->menuText($statusMessage)
            ->clearButtons()
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

        $this->telegramAccountService->removeLinkAccount($bot->userId());

        $message = "❌ **Отвязка аккаунта**\n\n" .
            "Ваш аккаунт был отвязан от системы FitnessCoach\n\n" .
            "Для повторной привязки используйте функцию \"Получить код для привязки\"";

        $bot->sendMessage($message, parse_mode: ParseMode::MARKDOWN);
        $this->end();
        MainMenu::begin($bot);
    }

    public function backToAccountLinking(Nutgram $bot)
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
