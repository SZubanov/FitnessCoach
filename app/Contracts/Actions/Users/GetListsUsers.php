<?php
declare(strict_types=1);

namespace App\Contracts\Actions\Users;

use Illuminate\Database\Eloquent\Collection;

interface GetListsUsers
{
    public function __invoke(): Collection;
}
