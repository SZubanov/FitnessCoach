<?php
declare(strict_types=1);

namespace App\Dto\Web;

use Spatie\LaravelData\Data;

class UserStoreDto extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public ?int $role,
    )
    {

    }
}
