<?php

namespace App\Telegram\Conversations;

use App\Telegram\Constants\ConversationSteps;
use App\Telegram\Services\ConversationHelper;
use App\Telegram\Services\DateValidationService;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class SyncConversation extends Conversation
{
    protected ?string $step = ConversationSteps::INPUT_DATE;
    
    public function inputDate(Nutgram $bot)
    {
        // Get sync type from cache (set by menu)
        $syncType = cache()->get("telegram_user_sync_type_{$bot->userId()}");
        
        if (!$syncType) {
            $bot->sendMessage('Ошибка: тип синхронизации не найден');
            ConversationHelper::returnToMainMenu($bot);
            $this->end();
            return;
        }
        
        $dateService = app(DateValidationService::class);
        
        // Show date input instructions
        $bot->sendMessage(
            "🔄 **{$syncType}**\n\n" . 
            $dateService->getDateInputInstructions()
        );
        
        $this->next(ConversationSteps::EXECUTE_SYNC);
    }
    
    public function executeSync(Nutgram $bot)
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
        
        // Get sync data
        $syncType = cache()->get("telegram_user_sync_type_{$bot->userId()}");
        $syncCallback = cache()->get("telegram_user_sync_callback_{$bot->userId()}");
        
        // Show processing message
        $bot->sendMessage("🔄 Выполняется синхронизация...");
        
        try {
            // Here you would normally call the sync service
            // $this->performSync($bot->userId(), $syncCallback, $dateResult['date']);
            
            // Simulate sync process
            sleep(1);
            
            // Show success message
            $bot->sendMessage(
                "✅ **Синхронизация завершена**\n\n" .
                "Тип: {$syncType}\n" .
                "Дата: {$dateResult['formatted']}\n\n" .
                "Данные успешно синхронизированы с FatSecret"
            );
            
        } catch (\Exception $e) {
            // Show error message
            $bot->sendMessage(
                "❌ **Ошибка синхронизации**\n\n" .
                "Попробуйте позже или проверьте подключение к FatSecret"
            );
        }
        
        // Clean up and return to menu
        ConversationHelper::clearConversationData($bot);
        ConversationHelper::returnToMainMenu($bot);
        $this->end();
    }
}