<?php

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
Route::get('/admin/users', UserPageListController::class)->name('web.users.page.list');
Route::get('/admin/users/list', UserDataListController::class)->name('web.users.data.list');


