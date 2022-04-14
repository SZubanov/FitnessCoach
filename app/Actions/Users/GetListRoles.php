<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetListRolesInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class GetListRoles implements GetListRolesInterface
{
    public function __invoke(): Collection
    {
        return Role::select('id', 'name')->get();
    }
}
