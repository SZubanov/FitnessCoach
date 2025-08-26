<?php

namespace App\Telegram\Commands;

use App\Actions\Users\GetUserByTelegramId;
use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class StartCommand extends Command
{
    protected string $command = 'start';

    protected ?string $description = 'Start conversation with bot';

    public function handle(Nutgram $bot, GetUserByTelegramId $getUserByTelegramId): void
    {
        $telegramUser = $bot->user();
        $user = $getUserByTelegramId(123123123);
        if (!$user) {

        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('📏 Замеры тела', callback_data: 'measurements_start'),
                InlineKeyboardButton::make('🔄 Синхронизация', callback_data: 'sync_start')
            )
            ->addRow(
                InlineKeyboardButton::make('❓ Помощь', callback_data: 'help')
            );

        $welcomeText = "👋 Добро пожаловать в FitnessCoach!\n\n";
        $welcomeText .= "Этот бот поможет вам:\n";
        $welcomeText .= "• 📏 Записывать замеры тела\n";
        $welcomeText .= "• 🔄 Синхронизировать данные с FatSecret\n\n";
        $welcomeText .= "Выберите действие:";

        $bot->sendMessage($welcomeText, reply_markup: $keyboard);
    }
}
