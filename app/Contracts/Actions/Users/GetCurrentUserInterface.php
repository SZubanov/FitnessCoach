<?php

namespace App\Contracts\Actions\Users;

use App\Models\User;

interface GetCurrentUserInterface
{
    public function __invoke(): User;
}
