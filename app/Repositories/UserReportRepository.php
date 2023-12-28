<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Dto\UserReport\UserFoodEntryDto;
use App\Dto\UserReport\UserStepsDto;
use App\Dto\UserReport\UserWeightDto;
use App\Models\FoodEntry;
use App\Models\UserStep;
use App\Models\UserWeight;

class UserReportRepository
{
    /**
     * @param UserFoodEntryDto $dto
     * @return void
     */
    public function createUserFoodEntry(UserFoodEntryDto $dto): void
    {
       FoodEntry::updateOrCreate(
            [
                'user_id' => $dto->getUserId(),
                'date' => $dto->getDate()
            ],
            [
                'calories' => $dto->getCalories(),
                'protein' => $dto->getProtein(),
                'fat' => $dto->getFat(),
                'carbohydrate' => $dto->getCarbohydrate(),
                'unit' => $dto->getUnit()
            ]
        );
    }

    /**
     * @param UserWeightDto $dto
     * @return void
     */
    public function createUserWeight(UserWeightDto $dto): void
    {
        UserWeight::updateOrCreate(
            [
                'user_id' => $dto->getUserId(),
                'date' => $dto->getDate()
            ],
            [
                'weight' => $dto->getWeight(),
                'unit' => $dto->getUnit()
            ]
        );
    }

    /**
     * @param UserStepsDto $dto
     * @return void
     */
    public function createUserSteps(UserStepsDto $dto): void
    {
        UserStep::updateOrCreate(
            [
                'user_id' => $dto->getUserId(),
                'date' => $dto->getDate()
            ],
            [
                'steps' => $dto->getSteps(),
            ]
        );
    }
}
