<?php

namespace App\FatSecret\Dto;

use App\Helpers\WeightUnit;
use Illuminate\Support\Collection;

class FoodEntryDto
{
    public function __construct(
        private Collection $foodMacronutrientDto,
        private string $weightUnit = WeightUnit::G
    ) {

    }

    /**
     * @return Collection
     */
    public function getFoodMacronutrientDto(): Collection
    {
        return $this->foodMacronutrientDto;
    }

    /**
     * @return string
     */
    public function getWeightUnit(): string
    {
        return $this->weightUnit;
    }


}
