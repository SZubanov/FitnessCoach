<?php

namespace App\Repositories;

use App\Dto\UserReport\UserReportDto;
use App\Models\UserReport;

class UserReportRepository
{
    /**
     * @param UserReportDto $dto
     * @return void
     */
    public function createUserReport(UserReportDto $dto): void
    {
       UserReport::updateOrCreate(
            [
                'user_id' => $dto->getUserId(),
                'date' => $dto->getDate()
            ],
            [
                'calories' => $dto->getCalories(),
                'protein' => $dto->getProtein(),
                'fat' => $dto->getFat(),
                'carbohydrate' => $dto->getCarbohydrate()
            ]
        );
    }
}
