<?php

namespace App\Contracts\Actions\Users;

interface GetDefaultWeightUnitUserInterface
{
    public function __invoke(): string;
}
