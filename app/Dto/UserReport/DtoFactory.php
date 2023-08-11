<?php

namespace App\Dto\UserReport;

use App\FatSecret\Dto\FoodMacronutrientDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DtoFactory
{
    public function createUserReportDto(int $userId, Carbon $date, Collection $foodMacronutrients)
    {
        /** @var FoodMacronutrientDto $foodMacronutrient */
        return new UserReportDto(
            $userId,
            $foodMacronutrients->sum(fn ($foodMacronutrient) => $foodMacronutrient->getCalories()),
            $foodMacronutrients->sum(fn ($foodMacronutrient) => $foodMacronutrient->getProtein()),
            $foodMacronutrients->sum(fn ($foodMacronutrient) => $foodMacronutrient->getFat()),
            $foodMacronutrients->sum(fn ($foodMacronutrient) => $foodMacronutrient->getCarbohydrate()),
            $date
        );
    }
}
