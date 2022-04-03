<?php
declare(strict_types=1);

namespace App\Dto\Web;

use App\Models\User;
use Spatie\LaravelData\Data;

class FormDto extends Data
{
    public function __construct(
        public ?User $user,
        public string $action,
        public string $html,
        public string $title,
        public string $button
    )
    {

    }
}
