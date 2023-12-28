<?php

namespace App\Dto\UserReport;

use Carbon\Carbon;

class UserStepsDto
{
    public function __construct(
        private int $userId,
        private int $steps,
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
     * @return float
     */
    public function getSteps(): float
    {
        return $this->steps;
    }

    /**
     * @return Carbon
     */
    public function getDate(): Carbon
    {
        return $this->date;
    }
}
