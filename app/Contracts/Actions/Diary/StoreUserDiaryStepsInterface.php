<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiaryStepsStoreDto;

interface StoreUserDiaryStepsInterface
{
    public function __invoke(DiaryStepsStoreDto $dto): void;
}
