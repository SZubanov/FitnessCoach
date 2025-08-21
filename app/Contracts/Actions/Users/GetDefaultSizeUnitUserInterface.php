<?php

namespace App\Contracts\Actions\Users;

use App\Models\User;

interface GetDefaultSizeUnitUserInterface
{
    public function __invoke(User $user): string;
}
