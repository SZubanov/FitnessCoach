<?php
declare(strict_types=1);

namespace App\Dto\Web\Diary;

use Spatie\LaravelData\Data;

class DiaryWeightStoreDto extends Data
{
    public function __construct(
        public string $date,
        public float $weight
    ) {

    }
}
