<?php

namespace App\Repositories;

use App\Dto\UserReport\UserFoodEntry;
use App\Dto\UserReport\UserWeightDto;
use App\Models\FoodEntry;
use App\Models\UserWeight;

class UserReportRepository
{
    /**
     * @param UserFoodEntry $dto
     * @return void
     */
    public function createUserFoodEntry(UserFoodEntry $dto): void
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
}
