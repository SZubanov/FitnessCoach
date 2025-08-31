<?php

namespace App\Telegram\Conversations;

use App\Telegram\Constants\ConversationSteps;
use App\Telegram\Services\ConversationHelper;
use App\Telegram\Services\DateValidationService;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class MacroConversation extends Conversation
{
    protected ?string $step = ConversationSteps::INPUT_DATE;
    
    public function inputDate(Nutgram $bot)
    {
        // Get macro type from cache (set by menu)
        $macroType = cache()->get("telegram_user_macro_type_{$bot->userId()}");
        
        if (!$macroType) {
            $bot->sendMessage('Ошибка: тип макронутриента не найден');
            ConversationHelper::returnToMainMenu($bot);
            $this->end();
            return;
        }
        
        $dateService = app(DateValidationService::class);
        
        // Show date input instructions
        $bot->sendMessage($dateService->getDateInputInstructions());
        
        $this->next(ConversationSteps::INPUT_MACRO_VALUE);
    }
    
    public function inputMacroValue(Nutgram $bot)
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
        
        // Get macro type
        $macroType = cache()->get("telegram_user_macro_type_{$bot->userId()}");
        
        // Ask for macro value
        $bot->sendMessage(
            "{$macroType['icon']} **{$macroType['name']}**\n\n" .
            "Введите значение в {$macroType['unit']}:\n" .
            "Например: " . ($macroType['name'] === 'калории' ? '2000' : '100')
        );
        
        $this->next(ConversationSteps::SHOW_SUCCESS);
    }
    
    public function showSuccess(Nutgram $bot)
    {
        $valueInput = $bot->message()?->text;
        
        if (!$valueInput || !is_numeric($valueInput)) {
            $macroType = cache()->get("telegram_user_macro_type_{$bot->userId()}");
            $bot->sendMessage(
                "❌ **Неверное значение**\n\n" .
                "Введите число в {$macroType['unit']}:\n" .
                "Например: " . ($macroType['name'] === 'калории' ? '2000' : '100')
            );
            return;
        }
        
        $value = (float) $valueInput;
        $macroType = cache()->get("telegram_user_macro_type_{$bot->userId()}");
        
        // Validate ranges based on macro type
        $validRange = match($macroType['name']) {
            'калории' => ['min' => 500, 'max' => 5000],
            default => ['min' => 0, 'max' => 1000] // For proteins, fats, carbs
        };
        
        if ($value < $validRange['min'] || $value > $validRange['max']) {
            $bot->sendMessage(
                "❌ **Значение вне допустимого диапазона**\n\n" .
                "Введите значение от {$validRange['min']} до {$validRange['max']} {$macroType['unit']}:"
            );
            return;
        }
        
        // Get stored data
        $date = ConversationHelper::getConversationData($bot, 'date');
        
        // Here you would normally save to database
        // $this->saveMacro($bot->userId(), $macroType['name'], $value, $date);
        
        // Show success message
        $bot->sendMessage(
            "✅ **КБЖУ сохранено**\n\n" .
            "Тип: {$macroType['name']}\n" .
            "Значение: {$value} {$macroType['unit']}\n" .
            "Дата: {$date}"
        );
        
        // Clean up and return to menu
        ConversationHelper::clearConversationData($bot);
        ConversationHelper::returnToMainMenu($bot);
        $this->end();
    }
}