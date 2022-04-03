<?php
declare(strict_types=1);

namespace App\Contracts\Actions\Users;

use Illuminate\Database\Eloquent\Collection;

interface GetListUserInterface
{
    public function __invoke(): Collection;
}
