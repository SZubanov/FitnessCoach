<?php

namespace App\Telegram\Conversations;

use App\Telegram\Constants\ConversationSteps;
use App\Telegram\Services\ConversationHelper;
use App\Telegram\Services\DateValidationService;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class WeightConversation extends Conversation
{
    protected ?string $step = ConversationSteps::INPUT_DATE;
    
    public function inputDate(Nutgram $bot)
    {
        $dateService = app(DateValidationService::class);
        
        // Show date input instructions
        $bot->sendMessage($dateService->getDateInputInstructions());
        
        $this->next(ConversationSteps::INPUT_WEIGHT_VALUE);
    }
    
    public function inputWeightValue(Nutgram $bot)
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
        
        // Ask for weight value
        $bot->sendMessage(
            "⚖️ **Запись веса**\n\n" .
            "Введите ваш вес в килограммах:\n" .
            "Например: 70.5 или 85.2"
        );
        
        $this->next(ConversationSteps::SHOW_SUCCESS);
    }
    
    public function showSuccess(Nutgram $bot)
    {
        $valueInput = $bot->message()?->text;
        
        if (!$valueInput || !is_numeric(str_replace(',', '.', $valueInput))) {
            $bot->sendMessage(
                "❌ **Неверное значение**\n\n" .
                "Введите число (вес в килограммах):\n" .
                "Например: 70.5 или 85,2"
            );
            return;
        }
        
        $value = (float) str_replace(',', '.', $valueInput);
        
        if ($value < 20 || $value > 300) {
            $bot->sendMessage(
                "❌ **Значение вне допустимого диапазона**\n\n" .
                "Введите вес от 20 до 300 кг:"
            );
            return;
        }
        
        // Get stored data
        $date = ConversationHelper::getConversationData($bot, 'date');
        
        // Here you would normally save to database
        // $this->saveWeight($bot->userId(), $value, $date);
        
        // Show success message
        $bot->sendMessage(
            "✅ **Вес сохранен**\n\n" .
            "Значение: {$value} кг\n" .
            "Дата: {$date}"
        );
        
        // Clean up and return to menu
        ConversationHelper::clearConversationData($bot);
        ConversationHelper::returnToMainMenu($bot);
        $this->end();
    }
}