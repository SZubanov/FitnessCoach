<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\StoreUserInterface;
use App\Dto\Web\UserStoreDto;
use App\Models\User;
use Carbon\Carbon;
use Hash;

class StoreUser implements StoreUserInterface
{
    public function __invoke(UserStoreDto $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'email_verified_at' => Carbon::now()
        ]);
    }
}
