<?php
declare(strict_types=1);

namespace App\Dto\Web\Diary;

use Spatie\LaravelData\Data;

class DiarySizesStoreDto extends Data
{
    public function __construct(
        public string $date,
        public ?float $neck,
        public ?float $chest,
        public ?float $waist,
        public ?float $biceps,
        public ?float $pelvis,
        public ?float $thigh,
        public ?float $tibia
    ) {

    }
}
