<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetDefaultSizeUnitUserInterface;
use App\Helpers\MetricSystem;
use App\Models\User;

class GetDefaultSizeUnitUser implements GetDefaultSizeUnitUserInterface
{
    public function __invoke(User $user): string
    {
        return MetricSystem::getDefaultSizeUnitByMetricSystem($user->default_measure_system);
    }
}
