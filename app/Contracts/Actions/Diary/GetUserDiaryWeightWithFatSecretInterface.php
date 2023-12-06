<?php

namespace App\Contracts\Actions\Diary;

use Carbon\Carbon;

interface GetUserDiaryWeightWithFatSecretInterface
{
    public function __invoke(Carbon $date);
}
