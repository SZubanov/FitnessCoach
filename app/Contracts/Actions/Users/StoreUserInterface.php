<?php
declare(strict_types=1);


namespace App\Contracts\Actions\Users;

use App\Dto\Web\UserStoreDto;
use App\Models\User;

interface StoreUserInterface
{
    public function __invoke(UserStoreDto $dto): User;
}
