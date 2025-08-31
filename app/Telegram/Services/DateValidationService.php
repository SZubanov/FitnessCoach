<?php

namespace App\Telegram\Services;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class DateValidationService
{
    public function validateAndParseDate(string $dateInput): array
    {
        $dateInput = trim($dateInput);
        
        $formats = [
            'd.m.Y',    // DD.MM.YYYY
            'd/m/Y',    // DD/MM/YYYY
            'd-m-Y',    // DD-MM-YYYY
            'd.n.Y',    // D.M.YYYY (single digit month)
            'd/n/Y',    // D/M/YYYY (single digit month)
        ];
        
        $parsedDate = null;
        
        foreach ($formats as $format) {
            try {
                $parsedDate = Carbon::createFromFormat($format, $dateInput);
                if ($parsedDate) {
                    break;
                }
            } catch (InvalidFormatException $e) {
                continue;
            }
        }
        
        if (!$parsedDate) {
            return [
                'valid' => false,
                'error' => $this->getInvalidFormatError(),
                'date' => null
            ];
        }
        
        $now = Carbon::now();
        $minDate = $now->copy()->subYears(5);
        
        if ($parsedDate->lt($minDate) || $parsedDate->gt($now)) {
            return [
                'valid' => false,
                'error' => $this->getDateRangeError(),
                'date' => null
            ];
        }
        
        return [
            'valid' => true,
            'error' => null,
            'date' => $parsedDate,
            'formatted' => $parsedDate->format('d.m.Y')
        ];
    }
    
    private function getInvalidFormatError(): string
    {
        return "❌ **Неверный формат даты**\n\n" .
               "Используйте формат ДД.ММ.ГГГГ или ДД/ММ/ГГГГ\n" .
               "Например: 25.12.2024 или 25/12/2024\n\n" .
               "Попробуйте еще раз:";
    }
    
    private function getDateRangeError(): string
    {
        return "❌ **Неверная дата**\n\n" .
               "Используйте дату не старше 5 лет и не из будущего\n\n" .
               "Попробуйте еще раз:";
    }
    
    public function getDateInputInstructions(): string
    {
        $today = Carbon::now()->format('d.m.Y');
        
        return "📅 **Введите дату**\n\n" .
               "Формат: ДД.ММ.ГГГГ или ДД/ММ/ГГГГ\n" .
               "Пример: {$today}\n\n" .
               "Введите дату:";
    }
}