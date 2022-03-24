<?php

use App\Http\Controllers\Web\Users\UserCreateFormController;
use App\Http\Controllers\Web\Users\UserDataListController;
use App\Http\Controllers\Web\Users\UserPageListController;
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
    return view('welcome');
});

Auth::routes();

Route::get('/home', [\App\Http\Controllers\Web\HomeController::class, 'index'])->name('home');
Route::group(['prefix' => 'admin/users'], static function() {
    Route::get('/', UserPageListController::class)->name('web.users.page.list');
    Route::get('/list', UserDataListController::class)->name('web.users.data.list');
    Route::get('/create', UserCreateFormController::class)->name('web.users.create.form');
});


