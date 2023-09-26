<?php

namespace App\Contracts\Actions\Diary;

use App\Dto\User\UserDto;

interface GetDiaryUserInterface
{
    public function __invoke(int $id): UserDto;
}
