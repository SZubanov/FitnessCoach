<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Datatables\UserElements;
use App\Actions\Datatables\UserListColumn;
use App\Actions\Diary\GetDiaryUser;
use App\Actions\Diary\GetUserDiaryMacrosWithFatSecret;
use App\Actions\Diary\GetUserDiaryWeightWithFatSecret;
use App\Actions\Diary\StoreUserDiaryMacros;
use App\Actions\Diary\StoreUserDiarySteps;
use App\Actions\Diary\StoreUserDiaryWeight;
use App\Actions\Telegram\GenerateLinkAccountCode;
use App\Actions\Telegram\LinkTelegramAccount;
use App\Actions\Telegram\UnlinkTelegramAccount;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\FatSecretLogout;
use App\Actions\Users\GetCurrentUser;
use App\Actions\Users\GetDefaultSizeUnitUser;
use App\Actions\Users\GetDefaultWeightUnitUser;
use App\Actions\Users\GetListRoles;
use App\Actions\Users\GetListUsers;
use App\Actions\Users\GetUserByTelegramId;
use App\Actions\Users\StoreUser;
use App\Actions\Users\UpdateUser;
use App\Contracts\Actions\Datatables\ResponseElementsInterface;
use App\Contracts\Actions\Datatables\UserListsColumnInterface;
use App\Contracts\Actions\Diary\GetDiaryUserInterface;
use App\Contracts\Actions\Diary\GetUserDiaryMacrosWithFatSecretInterface;
use App\Contracts\Actions\Diary\GetUserDiaryWeightWithFatSecretInterface;
use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Contracts\Actions\Diary\StoreUserDiaryStepsInterface;
use App\Contracts\Actions\Diary\StoreUserDiaryWeightInterface;
use App\Contracts\Actions\Telegram\GenerateLinkAccountCodeInterface;
use App\Contracts\Actions\Telegram\LinkTelegramAccountInterface;
use App\Contracts\Actions\Telegram\UnlinkTelegramAccountInterface;
use App\Contracts\Actions\Users\DeleteUserInterface;
use App\Contracts\Actions\Users\FatSecretLogoutInterface;
use App\Contracts\Actions\Users\GetCurrentUserInterface;
use App\Contracts\Actions\Users\GetDefaultSizeUnitUserInterface;
use App\Contracts\Actions\Users\GetDefaultWeightUnitUserInterface;
use App\Contracts\Actions\Users\GetListRolesInterface;
use App\Contracts\Actions\Users\GetListUserInterface;
use App\Contracts\Actions\Users\GetUserByTelegramIdInterface;
use App\Contracts\Actions\Users\StoreUserInterface;
use App\Contracts\Actions\Users\UpdateUserInterface;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public array $bindings = [
        GetListUserInterface::class => GetListUsers::class,
        UserListsColumnInterface::class => UserListColumn::class,
        ResponseElementsInterface::class => UserElements::class,
        StoreUserInterface::class => StoreUser::class,
        UpdateUserInterface::class => UpdateUser::class,
        DeleteUserInterface::class => DeleteUser::class,
        GetListRolesInterface::class => GetListRoles::class,
        GetCurrentUserInterface::class => GetCurrentUser::class,
        GetDiaryUserInterface::class => GetDiaryUser::class,
        StoreUserDiaryMacrosInterface::class => StoreUserDiaryMacros::class,
        GetUserDiaryMacrosWithFatSecretInterface::class => GetUserDiaryMacrosWithFatSecret::class,
        StoreUserDiaryWeightInterface::class => StoreUserDiaryWeight::class,
        GetUserDiaryWeightWithFatSecretInterface::class => GetUserDiaryWeightWithFatSecret::class,
        GetDefaultWeightUnitUserInterface::class => GetDefaultWeightUnitUser::class,
        GetDefaultSizeUnitUserInterface::class => GetDefaultSizeUnitUser::class,
        StoreUserDiaryStepsInterface::class => StoreUserDiarySteps::class,
        FatSecretLogoutInterface::class => FatSecretLogout::class,
        GetUserByTelegramIdInterface::class => GetUserByTelegramId::class,
        LinkTelegramAccountInterface::class => LinkTelegramAccount::class,
        UnlinkTelegramAccountInterface::class => UnlinkTelegramAccount::class,
        GenerateLinkAccountCodeInterface::class => GenerateLinkAccountCode::class,
    ];
}
