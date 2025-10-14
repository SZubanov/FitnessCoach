<?php

namespace App\Telegram\Services;

use App\Telegram\Menus\MainMenu;
use SergiX44\Nutgram\Nutgram;

class ConversationHelper
{
    public static function returnToMainMenu(Nutgram $bot): void
    {
        MainMenu::begin($bot);
    }
    
    public static function getSuccessMessage(string $type, string $value, string $date): string
    {
        return "✅ **Данные сохранены**\n\n" .
               "{$type}: {$value}\n" .
               "Дата: {$date}";
    }
    
    public static function getValueValidationError(string $type): string
    {
        return "❌ **Неверное значение**\n\n" .
               "Введите корректное значение для {$type}\n\n" .
               "Попробуйте еще раз:";
    }
    
    public static function storeConversationData(Nutgram $bot, string $key, mixed $value): void
    {
        cache()->put("telegram_conversation_{$bot->userId()}_{$key}", $value, 900);
    }
    
    public static function getConversationData(Nutgram $bot, string $key): mixed
    {
        return cache()->get("telegram_conversation_{$bot->userId()}_{$key}");
    }
    
    public static function clearConversationData(Nutgram $bot): void
    {
        $userId = $bot->userId();
        $keys = [
            "telegram_conversation_{$userId}_date",
            "telegram_conversation_{$userId}_value", 
            "telegram_conversation_{$userId}_type",
            "telegram_conversation_{$userId}_callback",
            "telegram_user_measurement_type_{$userId}",
            "telegram_user_macro_type_{$userId}",
            "telegram_user_sync_type_{$userId}"
        ];
        
        foreach ($keys as $key) {
            cache()->forget($key);
        }
    }
}