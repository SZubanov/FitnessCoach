<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetDefaultSizeUnitUserInterface;
use App\Helpers\MetricSystem;

class GetDefaultSizeUnitUser implements GetDefaultSizeUnitUserInterface
{
    public function __invoke(): string
    {
        return MetricSystem::getDefaultSizeUnitByMetricSystem(\Auth::user()->default_measure_system);
    }
}
