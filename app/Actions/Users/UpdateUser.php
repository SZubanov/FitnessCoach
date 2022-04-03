<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\UpdateUserInterface;
use App\Dto\Web\UserUpdateDto;
use App\Models\User;
use Hash;
use Illuminate\Support\Arr;

class UpdateUser implements UpdateUserInterface
{
    public function __invoke(User $user, UserUpdateDto $dto): User
    {
        $user->update(Arr::whereNotNull($dto->toArray()));
        return $user;
    }
}
