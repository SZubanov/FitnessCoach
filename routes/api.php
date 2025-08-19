<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\TelegramFatSecretAuthController;
use App\Http\Controllers\Api\TelegramFatSecretCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');

// Telegram FatSecret OAuth routes
Route::post('/telegram/fatsecret/auth', [TelegramFatSecretAuthController::class, 'initiate'])->name('telegram.fatsecret.auth');
Route::get('/telegram/fatsecret/callback', TelegramFatSecretCallbackController::class)->name('telegram.fatsecret.callback');
