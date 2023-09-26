<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\GetDiaryUserInterface;
use App\Dto\User\UserDto;
use App\Repositories\UserRepository;

class GetDiaryUser implements GetDiaryUserInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {

    }

    public function __invoke(int $id): UserDto
    {
        return $this->userRepository->getUserById($id);
    }
}
