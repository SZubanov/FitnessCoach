<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiaryMacrosStoreDto;

interface StoreUserDiaryMacrosInterface
{
    public function __invoke(DiaryMacrosStoreDto $dto): void;
}
