<?php

namespace App\Contracts\Actions\Users;

use Illuminate\Database\Eloquent\Collection;

interface GetListRolesInterface
{
    public function __invoke(): Collection;
}
