<?php

namespace App\Dto\UserReport;

use Carbon\Carbon;

class UserWeightDto
{
    public function __construct(
        private int $userId,
        private float $weight,
        private string $unit,
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
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * @return Carbon
     */
    public function getDate(): Carbon
    {
        return $this->date;
    }
}
