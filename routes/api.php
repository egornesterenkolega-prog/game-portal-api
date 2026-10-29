<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\UserController;
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
    // Гость: регистрация, вход, просмотр каталога, жанров, комментариев и профилей
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::apiResource('games', GameController::class)->only(['index', 'show']);
    Route::apiResource('genres', GenreController::class)->only(['index', 'show']);
    Route::apiResource('games.comments', CommentController::class)->shallow()->only(['index']);
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    // Требуют токен Sanctum (роли и политики — в ПР8)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::match(['put', 'patch'], 'me', [UserController::class, 'update'])->name('me.update');

        Route::apiResource('games', GameController::class)->except(['index', 'show']);
        Route::apiResource('genres', GenreController::class)->except(['index', 'show']);

        // Комментарии: добавление к игре; изменение и удаление по адресу /comments/{comment}
        Route::apiResource('games.comments', CommentController::class)->shallow()->only(['store', 'update', 'destroy']);

        // Оценка игры (не более одной от пользователя)
        Route::put('games/{game}/reaction', [ReactionController::class, 'update'])->name('games.reaction.update');
        Route::delete('games/{game}/reaction', [ReactionController::class, 'destroy'])->name('games.reaction.destroy');
    });
});
