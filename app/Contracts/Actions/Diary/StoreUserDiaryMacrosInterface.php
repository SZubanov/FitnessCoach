<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiaryMacrosStoreDto;
use App\Models\User;

interface StoreUserDiaryMacrosInterface
{
    public function __invoke(DiaryMacrosStoreDto $dto, User $user): void;
}
