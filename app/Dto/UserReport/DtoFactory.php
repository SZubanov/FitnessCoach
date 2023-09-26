<?php

namespace App\Dto\UserReport;

use App\FatSecret\Dto\FoodEntryDto;
use App\FatSecret\Dto\FoodMacronutrientDto;
use App\FatSecret\Dto\WeightDto;
use Carbon\Carbon;

class DtoFactory
{
    /**
     * @param int $userId
     * @param Carbon $date
     * @param FoodEntryDto $foodEntry
     * @return UserFoodEntry
     */
    public function createUserFoodEntryFromFoodEntry(int $userId, Carbon $date, FoodEntryDto $foodEntry): UserFoodEntry
    {
        $foodMacronutrientCollection = $foodEntry->getFoodMacronutrientDto();
        /** @var FoodMacronutrientDto $foodMacronutrient */
        return new UserFoodEntry(
            $userId,
            $foodMacronutrientCollection->sum(
                fn($foodMacronutrient) => $foodMacronutrient->getCalories()),

            round($foodMacronutrientCollection->sum(
                fn($foodMacronutrient) => $foodMacronutrient->getProtein()
            ), 2),

            round($foodMacronutrientCollection->sum(
                fn($foodMacronutrient) => $foodMacronutrient->getFat()
            ), 2),

            round($foodMacronutrientCollection->sum(
                fn($foodMacronutrient) => $foodMacronutrient->getCarbohydrate()
            ), 2),

            $foodEntry->getWeightUnit(),
            $date
        );
    }

    /**
     * @param int $userId
     * @param Carbon $date
     * @param WeightDto $weight
     * @return UserWeightDto
     */
    public function createUserWeightDto(int $userId, Carbon $date, WeightDto $weight): UserWeightDto
    {
        return new UserWeightDto(
            $userId,
            round($weight->getWeight(), 2),
            $weight->getUnit(),
            $date
        );
    }
}
