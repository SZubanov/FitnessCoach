<?php

namespace App\Telegram\Menus;

use App\Telegram\Constants\CallbackData;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class MacrosMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $instructionsText = "🍎 **КБЖУ - Макронутриенты**\n\n" .
                           "Выберите тип макронутриента для записи:\n\n" .
                           "💡 **Доступно:**\n" .
                           "🔥 **Калории** - Общая энергетическая ценность\n" .
                           "🥩 **Белки** - Протеины в граммах\n" .
                           "🧈 **Жиры** - Липиды в граммах\n" .
                           "🍞 **Углеводы** - Карбогидраты в граммах\n\n" .
                           "📅 Укажите дату для записи данных";

        $this->menuText($instructionsText)
            ->addButtonRow(
                InlineKeyboardButton::make('🔥 Калории', callback_data: CallbackData::MACROS_CALORIES . '@selectMacroType'),
                InlineKeyboardButton::make('🥩 Белки', callback_data: CallbackData::MACROS_PROTEINS . '@selectMacroType')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🧈 Жиры', callback_data: CallbackData::MACROS_FATS . '@selectMacroType'),
                InlineKeyboardButton::make('🍞 Углеводы', callback_data: CallbackData::MACROS_CARBS . '@selectMacroType')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🏠 Главное меню', callback_data: CallbackData::MAIN_MENU . '@backToMain')
            )
            ->orNext('none')
            ->showMenu();
    }

    public function selectMacroType(Nutgram $bot)
    {
        $callbackData = $bot->callbackQuery()->data;

        // Extract macro type from callback data
        $macroInfo = match($callbackData) {
            CallbackData::MACROS_CALORIES => ['name' => 'калории', 'unit' => 'ккал', 'icon' => '🔥'],
            CallbackData::MACROS_PROTEINS => ['name' => 'белки', 'unit' => 'г', 'icon' => '🥩'],
            CallbackData::MACROS_FATS => ['name' => 'жиры', 'unit' => 'г', 'icon' => '🧈'],
            CallbackData::MACROS_CARBS => ['name' => 'углеводы', 'unit' => 'г', 'icon' => '🍞'],
            default => ['name' => 'неизвестно', 'unit' => '', 'icon' => '❓']
        };

        // Store macro type in user session/cache
        cache()->put("telegram_user_macro_type_{$bot->userId()}", $macroInfo, 900);
        cache()->put("telegram_user_macro_callback_{$bot->userId()}", $callbackData, 900);

        // Ask for value
        $bot->sendMessage(
            "{$macroInfo['icon']} **{$macroInfo['name']}**\n\n" .
            "Введите значение в {$macroInfo['unit']}:\n" .
            "Например: " . ($macroInfo['name'] === 'калории' ? '2000' : '100')
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
        $bot->sendMessage('Используйте кнопки меню для выбора типа макронутриента.');
        $this->start($bot);
    }
}
