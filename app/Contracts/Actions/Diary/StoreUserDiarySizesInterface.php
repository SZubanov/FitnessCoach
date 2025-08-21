<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiarySizesStoreDto;
use App\Models\User;

interface StoreUserDiarySizesInterface
{
    public function __invoke(DiarySizesStoreDto $dto, User $user): void;
}
