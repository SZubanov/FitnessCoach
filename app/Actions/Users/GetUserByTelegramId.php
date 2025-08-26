<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\Models\User;
use App\Repositories\UserRepository;

readonly class GetUserByTelegramId implements GetUserByTelegramIdInterface
{
    public function __construct(private UserRepository $userRepository)
    {

    }

    public function __invoke(int $telegramUserId): ?User
    {
        return $this->userRepository->getUserByTelegramId($telegramUserId);
    }
}
