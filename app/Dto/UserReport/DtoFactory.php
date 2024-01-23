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
     * @return UserFoodEntryDto
     */
    public function createUserFoodEntryFromFoodEntry(
        int $userId,
        Carbon $date,
        FoodEntryDto $foodEntry
    ): UserFoodEntryDto {
        $foodMacronutrientCollection = $foodEntry->getFoodMacronutrientDto();
        /** @var FoodMacronutrientDto $foodMacronutrient */
        return new UserFoodEntryDto(
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

    public function createUserFoodEntryDto(
        int $userId,
        int $calories,
        float $protein,
        float $fat,
        float $carbohydrate,
        string $unit,
        Carbon $date
    ): UserFoodEntryDto {
        return new UserFoodEntryDto(
            $userId,
            $calories,
            $protein,
            $fat,
            $carbohydrate,
            $unit,
            $date
        );
    }

    /**
     * @param int $userId
     * @param Carbon $date
     * @param float $weight
     * @param string $unit
     * @return UserWeightDto
     */
    public function createUserWeightDto(
        int $userId,
        Carbon $date,
        float $weight,
        string $unit
    ): UserWeightDto {
        return new UserWeightDto(
            $userId,
            round($weight, 2),
            $unit,
            $date
        );
    }

    /**
     * @param int $userId
     * @param int $steps
     * @param Carbon $date
     * @return UserStepsDto
     */
    public function createUserStepsDto(
        int $userId,
        int $steps,
        Carbon $date
    ): UserStepsDto {
        return new UserStepsDto(
            $userId,
            $steps,
            $date
        );
    }

    /**
     * @param int $userId
     * @param float $neck
     * @param float $chest
     * @param float $waist
     * @param float $biceps
     * @param float $pelvis
     * @param float $thigh
     * @param float $tibia
     * @param string $unit
     * @param Carbon $date
     * @return UserSizesDto
     */
    public function createUserSizesDto(
        int $userId,
        float $neck,
        float $chest,
        float $waist,
        float $biceps,
        float $pelvis,
        float $thigh,
        float $tibia,
        string $unit,
        Carbon $date,
    ): UserSizesDto {
        return new UserSizesDto(
            $userId,
            $neck,
            $chest,
            $waist,
            $biceps,
            $pelvis,
            $thigh,
            $tibia,
            $unit,
            $date
        );
    }
}
