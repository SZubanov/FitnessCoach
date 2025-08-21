<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiaryWeightStoreDto;
use App\Models\User;

interface StoreUserDiaryWeightInterface
{
    public function __invoke(DiaryWeightStoreDto $dto, User $user): void;
}
