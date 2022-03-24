<?php
declare(strict_types=1);

namespace App\Providers;

use App\Actions\Datatables\UserElements;
use App\Actions\Datatables\UserListColumns;
use App\Actions\Users\GetListUsers;
use App\Contracts\Actions\Datatables\ResponseElements;
use App\Contracts\Actions\Datatables\UserListsColumns;
use App\Contracts\Actions\Users\GetListsUsers;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public array $bindings = [
        GetListsUsers::class => GetListUsers::class,
        UserListsColumns::class => UserListColumns::class,
        ResponseElements::class => UserElements::class
    ];
}
