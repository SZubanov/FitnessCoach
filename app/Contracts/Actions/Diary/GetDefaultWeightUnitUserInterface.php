<?php

namespace App\Contracts\Actions\Diary;

use App\Helpers\MetricSystem;

interface GetDefaultWeightUnitUserInterface
{
    public function __invoke(): string;
}
