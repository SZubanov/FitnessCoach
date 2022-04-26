<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Models\User;

class GetCurrentUser implements GetCurrentUserInterface
{
    public function __invoke(): User
    {
        return \Auth::user();
    }
}
