<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiaryWeightStoreDto;

interface StoreUserDiaryWeightInterface
{
    public function __invoke(DiaryWeightStoreDto $dto): void;
}
