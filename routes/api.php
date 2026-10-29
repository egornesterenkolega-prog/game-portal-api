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

    // Нужен токен Sanctum, и пользователь не должен быть заблокирован
    Route::middleware(['auth:sanctum', 'not.blocked'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::match(['put', 'patch'], 'me', [UserController::class, 'update'])->name('me.update');

        // Игры: создаёт любой пользователь; изменение и удаление проверяет GamePolicy
        Route::apiResource('games', GameController::class)->except(['index', 'show']);

        // Комментарии: добавление; изменение и удаление проверяет CommentPolicy
        Route::apiResource('games.comments', CommentController::class)->shallow()->only(['store', 'update', 'destroy']);

        // Оценка игры (не более одной от пользователя)
        Route::put('games/{game}/reaction', [ReactionController::class, 'update'])->name('games.reaction.update');
        Route::delete('games/{game}/reaction', [ReactionController::class, 'destroy'])->name('games.reaction.destroy');

        // Только администратор: управление жанрами и блокировка пользователей
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('genres', GenreController::class)->except(['index', 'show']);
            Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
            Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
        });
    });
});
