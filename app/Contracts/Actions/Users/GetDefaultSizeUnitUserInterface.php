<?php

namespace App\Contracts\Actions\Users;

interface GetDefaultSizeUnitUserInterface
{
    public function __invoke(): string;
}
