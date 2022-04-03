<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetListUserInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetListUsers implements GetListUserInterface
{
    public function __invoke(): Collection
    {
        return User::select('id','name', 'email', 'created_at')->get();
    }
}
