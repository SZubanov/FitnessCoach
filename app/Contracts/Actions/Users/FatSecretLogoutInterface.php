<?php

namespace App\Contracts\Actions\Users;

use App\Exceptions\FatSecretLogoutException;
use App\Models\User;

interface FatSecretLogoutInterface
{
    /**
     * @param User $user
     * @return bool
     * @throws FatSecretLogoutException
     */
    public function __invoke(User $user): bool;
}
