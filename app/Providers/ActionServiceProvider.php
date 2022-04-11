<?php
declare(strict_types=1);

namespace App\Providers;

use App\Actions\Datatables\UserElements;
use App\Actions\Datatables\UserListColumn;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\GetListUsers;
use App\Actions\Users\StoreUser;
use App\Actions\Users\UpdateUser;
use App\Contracts\Actions\Datatables\ResponseElementsInterface;
use App\Contracts\Actions\Datatables\UserListsColumnInterface;
use App\Contracts\Actions\Users\DeleteUserInterface;
use App\Contracts\Actions\Users\GetListUserInterface;
use App\Contracts\Actions\Users\StoreUserInterface;
use App\Contracts\Actions\Users\UpdateUserInterface;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public array $bindings = [
        GetListUserInterface::class      => GetListUsers::class,
        UserListsColumnInterface::class  => UserListColumn::class,
        ResponseElementsInterface::class => UserElements::class,
        StoreUserInterface::class        => StoreUser::class,
        UpdateUserInterface::class       => UpdateUser::class,
        DeleteUserInterface::class       => DeleteUser::class
    ];
}
