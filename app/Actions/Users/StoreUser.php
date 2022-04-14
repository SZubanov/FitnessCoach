<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\StoreUserInterface;
use App\Dto\Web\UserStoreDto;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Hash;
use Throwable;

class StoreUser implements StoreUserInterface
{
    /**
     * @throws Throwable
     */
    public function __invoke(UserStoreDto $dto): User
    {

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'email_verified_at' => Carbon::now(),
                'role_id' => $dto->role,
            ]);

            $user->assignRole($dto->role);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }
}
