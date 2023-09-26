<?php
declare(strict_types=1);

namespace App\Services;

use App\Dto\User\UserDto;
use App\Repositories\UserRepository;

class UserDiaryService
{
    public function __construct(private readonly UserRepository $userRepository)
    {

    }

    public function getUser(int $id): UserDto
    {
        return $this->userRepository->getUserById($id);
    }
}
