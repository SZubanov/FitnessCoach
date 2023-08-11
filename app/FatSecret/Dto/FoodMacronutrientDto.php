<?php

namespace App\FatSecret\Dto;

class FoodMacronutrientDto
{
    public function __construct(
        private int $calories,
        private float $protein,
        private float $fat,
        private float $carbohydrate
    ) {
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


}
