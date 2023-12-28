<?php
declare(strict_types=1);

namespace App\Dto\Web\Diary;

use Spatie\LaravelData\Data;

class DiaryStepsStoreDto extends Data
{
    public function __construct(
        public string $date,
        public int $steps
    ) {

    }
}
