<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\Web\Diary\DiarySizesStoreDto;

interface StoreUserDiarySizesInterface
{
    public function __invoke(DiarySizesStoreDto $dto): void;
}
