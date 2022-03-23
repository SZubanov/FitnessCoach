<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetListsUsers;
use App\Models\User;

class GetListUsers implements GetListsUsers
{
    public function __invoke()
    {
        return User::select('id','name', 'email', 'created_at')->get();
    }
}
