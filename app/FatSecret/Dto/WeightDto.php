<?php

namespace App\FatSecret\Dto;

use App\Helpers\WeightUnit;

class WeightDto
{
    public function __construct(
        private float $weight,
        private string $unit = WeightUnit::KG
    )
    {

    }
}
