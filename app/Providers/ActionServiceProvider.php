<?php
declare(strict_types=1);

namespace App\Providers;

use App\Actions\Datatables\UserElements;
use App\Actions\Datatables\UserListColumn;
use App\Actions\Diary\GetDiaryUser;
use App\Actions\Diary\GetUserDiaryMacrosWithFatSecret;
use App\Actions\Diary\StoreUserDiaryMacros;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\GetCurrentUser;
use App\Actions\Users\GetListRoles;
use App\Actions\Users\GetListUsers;
use App\Actions\Users\StoreUser;
use App\Actions\Users\UpdateUser;
use App\Contracts\Actions\Datatables\ResponseElementsInterface;
use App\Contracts\Actions\Datatables\UserListsColumnInterface;
use App\Contracts\Actions\Diary\GetDiaryUserInterface;
use App\Contracts\Actions\Diary\GetUserDiaryMacrosWithFatSecretInterface;
use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Contracts\Actions\Users\DeleteUserInterface;
use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Contracts\Actions\Users\GetListRolesInterface;
use App\Contracts\Actions\Users\GetListUserInterface;
use App\Contracts\Actions\Users\StoreUserInterface;
use App\Contracts\Actions\Users\UpdateUserInterface;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public array $bindings = [
        GetListUserInterface::class          => GetListUsers::class,
        UserListsColumnInterface::class      => UserListColumn::class,
        ResponseElementsInterface::class     => UserElements::class,
        StoreUserInterface::class            => StoreUser::class,
        UpdateUserInterface::class           => UpdateUser::class,
        DeleteUserInterface::class           => DeleteUser::class,
        GetListRolesInterface::class         => GetListRoles::class,
        GetCurrentUserInterface::class       => GetCurrentUser::class,
        GetDiaryUserInterface::class         => GetDiaryUser::class,
        StoreUserDiaryMacrosInterface::class => StoreUserDiaryMacros::class,
        GetUserDiaryMacrosWithFatSecretInterface::class => GetUserDiaryMacrosWithFatSecret::class
    ];
}
