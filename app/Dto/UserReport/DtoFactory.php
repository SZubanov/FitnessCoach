<?php

namespace App\Dto\UserReport;

use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\FoodMacronutrientDto;
use Carbon\Carbon;

class DtoFactory
{
    public function createUserReportDto(int $userId, Carbon $date, FoodEntryDto $foodEntry): UserReportDto
    {
        /** @var FoodMacronutrientDto $foodMacronutrient */
        return new UserReportDto(
            $userId,
            $foodEntry->getFoodMacronutrientDto()->sum(fn ($foodMacronutrient) => $foodMacronutrient->getCalories()),
            $foodEntry->getFoodMacronutrientDto()->sum(fn ($foodMacronutrient) => $foodMacronutrient->getProtein()),
            $foodEntry->getFoodMacronutrientDto()->sum(fn ($foodMacronutrient) => $foodMacronutrient->getFat()),
            $foodEntry->getFoodMacronutrientDto()->sum(fn ($foodMacronutrient) => $foodMacronutrient->getCarbohydrate()),
            $foodEntry->getWeightUnit(),
            $date
        );
    }
}
