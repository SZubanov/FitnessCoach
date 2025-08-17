<?php

namespace App\Actions\Users;

use App\Contracts\Actions\Users\FatSecretLogoutInterface;
use App\Exceptions\FatSecretLogoutException;
use App\Models\User;
use Throwable;

class FatSecretLogout implements FatSecretLogoutInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(User $user): bool
    {
        $user->oauth_token_secret = null;
        $user->oauth_token = null;

        try {
            $user->save();
        } catch (Throwable $exception) {
            \Log::error($exception->getMessage(), $exception->getTrace());
            throw new FatSecretLogoutException();
        }

        return true;
    }
}
