<?php
declare(strict_types=1);


namespace App\Contracts\Actions\Users;

use App\Dto\Web\UserUpdateDto;
use App\Models\User;

interface DeleteUserInterface
{
    public function __invoke(User $user): bool;
}
