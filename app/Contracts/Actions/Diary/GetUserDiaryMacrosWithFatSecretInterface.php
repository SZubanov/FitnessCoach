<?php

namespace App\Contracts\Actions\Diary;

use App\Models\User;
use Carbon\Carbon;

interface GetUserDiaryMacrosWithFatSecretInterface
{
    public function __invoke(Carbon $date, User $user);
}
