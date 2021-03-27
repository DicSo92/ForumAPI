<?php

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

Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register'])->name('register');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/me', [\App\Http\Controllers\AuthController::class, 'user'])->name('user');

    Route::get('/channels', [\App\Http\Controllers\ChannelController::class, 'index'])->name('index');

    Route::prefix('threads')->name('thread.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ThreadController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\ThreadController::class, 'store']);
        Route::get('/{thread}', [\App\Http\Controllers\ThreadController::class, 'show']);
        Route::put('/{thread}', [\App\Http\Controllers\ThreadController::class, 'update']);
        Route::delete('{thread}', [\App\Http\Controllers\ThreadController::class, 'destroy']);
        Route::get('{thread}/replies/{reply}', [\App\Http\Controllers\ReplyController::class, 'show']);
        Route::put('{thread}/replies/{reply}', [\App\Http\Controllers\ReplyController::class, 'update']);
        Route::delete('{thread}/replies/{reply}', [\App\Http\Controllers\ReplyController::class, 'destroy']);
        Route::post('{thread}/replies', [\App\Http\Controllers\ReplyController::class, 'store']);
    });
});
