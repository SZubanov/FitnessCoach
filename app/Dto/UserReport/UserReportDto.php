<?php

namespace App\Dto\UserReport;

use Carbon\Carbon;

class UserReportDto
{
    public function __construct(
        private int $userId,
        private int $calories,
        private float $protein,
        private float $fat,
        private float $carbohydrate,
        private Carbon $date
    ) {

    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getCalories(): int
    {
        return $this->calories;
    }

    /**
     * @return float
     */
    public function getProtein(): float
    {
        return $this->protein;
    }

    /**
     * @return float
     */
    public function getFat(): float
    {
        return $this->fat;
    }

    /**
     * @return float
     */
    public function getCarbohydrate(): float
    {
        return $this->carbohydrate;
    }

    /**
     * @return Carbon
     */
    public function getDate(): Carbon
    {
        return $this->date;
    }


}
