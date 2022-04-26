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
        DB::beginTransaction();
        try {
            $user->update($this->prepareData($dto));

            if (!is_null($dto->role)) {
                $user->syncRoles($dto->role);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }

    private function prepareData(UserUpdateDto $dto): array
    {
        $data = $dto->toArray();
        unset($data['role']);
        if (is_null($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }
}
