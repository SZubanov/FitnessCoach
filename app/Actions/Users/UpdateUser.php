<?php
declare(strict_types=1);

namespace App\Actions\Users;

use App\Contracts\Actions\Users\UpdateUserInterface;
use App\Dto\Web\UserUpdateDto;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Throwable;

class UpdateUser implements UpdateUserInterface
{
    /**
     * @throws Throwable
     */
    public function __invoke(User $user, UserUpdateDto $dto): User
    {
        $this->setPassword($dto);

        DB::beginTransaction();
        try {
            $user->update($dto->toArray());
            $user->syncRoles($dto->role);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }

    private function setPassword(UserUpdateDto $dto): void
    {
        if (is_null($dto->password)) {
            unset($dto->password);
        } else {
            $dto->password = Hash::make($dto->password);
        }
    }
}
