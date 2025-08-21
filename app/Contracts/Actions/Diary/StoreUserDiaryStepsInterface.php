<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiaryStepsStoreDto;
use App\Models\User;

interface StoreUserDiaryStepsInterface
{
    public function __invoke(DiaryStepsStoreDto $dto, User $user): void;
}
