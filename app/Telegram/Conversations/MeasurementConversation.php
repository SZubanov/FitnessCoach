<?php

namespace App\Telegram\Conversations;

use App\Telegram\Constants\ConversationSteps;
use App\Telegram\Services\ConversationHelper;
use App\Telegram\Services\DateValidationService;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class MeasurementConversation extends Conversation
{
    protected ?string $step = ConversationSteps::INPUT_DATE;
    
    public function inputDate(Nutgram $bot)
    {
        // Get measurement type from cache (set by menu)
        $measurementType = cache()->get("telegram_user_measurement_type_{$bot->userId()}");
        
        if (!$measurementType) {
            $bot->sendMessage('Ошибка: тип измерения не найден');
            ConversationHelper::returnToMainMenu($bot);
            $this->end();
            return;
        }
        
        $dateService = app(DateValidationService::class);
        
        // Show date input instructions
        $bot->sendMessage($dateService->getDateInputInstructions());
        
        $this->next(ConversationSteps::INPUT_MEASUREMENT_VALUE);
    }
    
    public function inputMeasurementValue(Nutgram $bot)
    {
        $dateService = app(DateValidationService::class);
        $dateInput = $bot->message()?->text;
        
        if (!$dateInput) {
            $bot->sendMessage('Введите дату текстом');
            return;
        }
        
        // Validate date
        $dateResult = $dateService->validateAndParseDate($dateInput);
        
        if (!$dateResult['valid']) {
            $bot->sendMessage($dateResult['error']);
            return;
        }
        
        // Store validated date
        ConversationHelper::storeConversationData($bot, 'date', $dateResult['formatted']);
        
        // Get measurement type
        $measurementType = cache()->get("telegram_user_measurement_type_{$bot->userId()}");
        
        // Ask for measurement value
        $bot->sendMessage(
            "📏 **Замер: {$measurementType}**\n\n" .
            "Введите значение в сантиметрах:\n" .
            "Например: 95"
        );
        
        $this->next(ConversationSteps::SHOW_SUCCESS);
    }
    
    public function showSuccess(Nutgram $bot)
    {
        $valueInput = $bot->message()?->text;
        
        if (!$valueInput || !is_numeric($valueInput)) {
            $bot->sendMessage(
                "❌ **Неверное значение**\n\n" .
                "Введите число (в сантиметрах):\n" .
                "Например: 95"
            );
            return;
        }
        
        $value = (float) $valueInput;
        
        if ($value < 1 || $value > 300) {
            $bot->sendMessage(
                "❌ **Значение вне допустимого диапазона**\n\n" .
                "Введите значение от 1 до 300 см:"
            );
            return;
        }
        
        // Get stored data
        $date = ConversationHelper::getConversationData($bot, 'date');
        $measurementType = cache()->get("telegram_user_measurement_type_{$bot->userId()}");
        
        // Here you would normally save to database
        // $this->saveMeasurement($bot->userId(), $measurementType, $value, $date);
        
        // Show success message
        $bot->sendMessage(
            "✅ **Замер сохранен**\n\n" .
            "Тип: {$measurementType}\n" .
            "Значение: {$value} см\n" .
            "Дата: {$date}"
        );
        
        // Clean up and return to menu
        ConversationHelper::clearConversationData($bot);
        ConversationHelper::returnToMainMenu($bot);
        $this->end();
    }
}