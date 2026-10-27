<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GenreController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Проверка работоспособности: название приложения, время и состояние БД.
Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
        $status = 200;
    } catch (\Throwable $e) {
        $database = 'error';
        $status = 503;
    }

    return response()->json([
        'app'      => config('app.name'),
        'time'     => now()->toIso8601String(),
        'database' => $database,
    ], $status);
});

// API версии 1: /api/v1/...
Route::prefix('v1')->name('v1.')->group(function () {
    // Гость: регистрация, вход, просмотр каталога и жанров
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::apiResource('games', GameController::class)->only(['index', 'show']);
    Route::apiResource('genres', GenreController::class)->only(['index', 'show']);

    // Требуют токен Sanctum (роли и политики — в ПР8)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::apiResource('games', GameController::class)->except(['index', 'show']);
        Route::apiResource('genres', GenreController::class)->except(['index', 'show']);
    });
});
