<?php

use App\Http\Controllers\Web\Admin\Users\UserCreateFormController as AdminUserCreateFormController;
use App\Http\Controllers\Web\Admin\Users\UserDataListController as AdminUserDataListController;
use App\Http\Controllers\Web\Admin\Users\UserDeleteController as AdminUserDeleteController;
use App\Http\Controllers\Web\Admin\Users\UserPageListController as AdminUserPageListController;
use App\Http\Controllers\Web\Admin\Users\UserStoreController as AdminUserStoreController;
use App\Http\Controllers\Web\Admin\Users\UserUpdateController as AdminUserUpdateController;
use App\Http\Controllers\Web\Admin\Users\UserUpdateFormController as AdminUserUpdateFormController;
use App\Http\Controllers\Web\Auth\ConfirmPasswordController;
use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\UserSetFatSecretTokenController;
use App\Http\Controllers\Web\UserUpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout']);
Route::group(['prefix' => 'password'], static function() {
    Route::get('/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('/confirm', [ConfirmPasswordController::class, 'confirm']);
    Route::post('/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
    Route::get('/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
});


Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::group(['middleware' => 'auth'], static function() {
    Route::get('/settings', SettingsController::class)->name('settings');
    Route::group(['prefix' => 'admin/users'], static function () {
        Route::get('/', AdminUserPageListController::class)->name('web.admin.users.page.list');
        Route::get('/list', AdminUserDataListController::class)->name('web.admin.users.data.list');
        Route::get('/create', AdminUserCreateFormController::class)->name('web.admin.users.create.form');
        Route::get('/{user}', AdminUserUpdateFormController::class)->name('web.admin.users.update.form');
        Route::post('/', AdminUserStoreController::class)->name('web.admin.users.store');
        Route::patch('/{user}', AdminUserUpdateController::class)->name('web.admin.users.update');
        Route::delete('/{user}', AdminUserDeleteController::class)->name('web.admin.users.delete');
    });

    Route::patch('/users/{user}', UserUpdateController::class)->name('web.users.update');
    Route::post('/fatsecret/{user}/token', UserSetFatSecretTokenController::class)->name('web.users.fatsecret.token');
    Route::get('/fatsecret/token/callback', \App\Http\Controllers\Web\UserSetFatSecretTokenCallbackController::class)->name('web.users.fatsecret.token.callback');
});


