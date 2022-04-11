<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\DeleteUserInterface;
use App\Models\User;

class DeleteUser implements DeleteUserInterface
{
    public function __invoke(User $user): bool
    {
        return $user->delete();
    }
}
