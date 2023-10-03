<?php

namespace App\Contracts\Actions\Diary;

use Carbon\Carbon;

interface GetUserDiaryMacrosWithFatSecretInterface
{
    public function __invoke(Carbon $date);
}
