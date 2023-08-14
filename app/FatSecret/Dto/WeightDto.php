<?php

namespace App\FatSecret\Dto;

use App\Helpers\WeightUnit;

class WeightDto
{
    public function __construct(
        private float $weight,
        private string $unit = WeightUnit::KG
    ) {
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
}
