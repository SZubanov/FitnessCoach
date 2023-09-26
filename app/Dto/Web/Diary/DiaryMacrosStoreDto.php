<?php
declare(strict_types=1);

namespace App\Dto\Web\Diary;

use Auth;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class DiaryMacrosStoreDto extends Data
{
    public function __construct(
        public string $date,
        public float $kcal,
        public float $protein,
        public float $fat,
        public float $carbs,
    ) {

    }
}
