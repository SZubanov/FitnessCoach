<?php
declare(strict_types=1);

namespace App\Dto\UserReport;

use Carbon\Carbon;

class UserSizesDto
{
    public function __construct(
        private int $userId,
        private float $neck,
        private float $chest,
        private float $waist,
        private float $biceps,
        private float $pelvis,
        private float $thigh,
        private float $tibia,
        private string $unit,
        private Carbon $date,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getNeck(): float
    {
        return $this->neck;
    }

    public function getChest(): float
    {
        return $this->chest;
    }

    public function getWaist(): float
    {
        return $this->waist;
    }

    public function getBiceps(): float
    {
        return $this->biceps;
    }

    public function getPelvis(): float
    {
        return $this->pelvis;
    }

    public function getThigh(): float
    {
        return $this->thigh;
    }

    public function getTibia(): float
    {
        return $this->tibia;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }
}
