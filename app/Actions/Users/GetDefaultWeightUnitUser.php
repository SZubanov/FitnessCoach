<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Diary\GetDefaultWeightUnitUserInterface;
use App\Helpers\MetricSystem;

class GetDefaultWeightUnitUser implements GetDefaultWeightUnitUserInterface
{
    public function __invoke(): string
    {
        return MetricSystem::getDefaultWeightUnitByMetricSystem(\Auth::user()->default_measure_system);
    }
}
