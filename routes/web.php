<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\ConfirmPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Users\UserCreateFormController;
use App\Http\Controllers\Web\Users\UserDataListController;
use App\Http\Controllers\Web\Users\UserDeleteController;
use App\Http\Controllers\Web\Users\UserPageListController;
use App\Http\Controllers\Web\Users\UserStoreController;
use App\Http\Controllers\Web\Users\UserUpdateController;
use App\Http\Controllers\Web\Users\UserUpdateFormController;
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
Route::group(['middleware' => ['auth'], 'prefix' => 'admin/users'], static function() {
    Route::get('/', UserPageListController::class)->name('web.users.page.list');
    Route::get('/list', UserDataListController::class)->name('web.users.data.list');
    Route::get('/create', UserCreateFormController::class)->name('web.users.create.form');
    Route::get('/{user}', UserUpdateFormController::class)->name('web.users.update.form');
    Route::post('/', UserStoreController::class)->name('web.users.store');
    Route::patch('/{user}', UserUpdateController::class)->name('web.users.update');
    Route::delete('/{user}', UserDeleteController::class)->name('web.users.delete');
});


