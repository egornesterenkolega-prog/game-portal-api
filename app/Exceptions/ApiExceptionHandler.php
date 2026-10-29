<?php

namespace App\Exceptions;

use App\Models\Comment;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Reaction;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Единый формат ошибок API: JSON с полем message,
 * для ошибок валидации дополнительно errors.
 * Подключается в bootstrap/app.php одной строкой.
 */
class ApiExceptionHandler
{
    private const NOT_FOUND = [
        Game::class => 'Игра не найдена.',
        Genre::class => 'Жанр не найден.',
        Comment::class => 'Комментарий не найден.',
        User::class => 'Пользователь не найден.',
        Reaction::class => 'Оценка не найдена.',
        Screenshot::class => 'Скриншот не найден.',
    ];

    private const DEFAULT_MESSAGES = [
        400 => 'Некорректный запрос.',
        401 => 'Требуется авторизация.',
        403 => 'Доступ запрещён.',
        404 => 'Ресурс не найден.',
        405 => 'Метод не поддерживается для этого адреса.',
        409 => 'Конфликт данных.',
        422 => 'Ошибка валидации.',
        429 => 'Слишком много запросов.',
    ];

    public static function register(Exceptions $exceptions): void
    {
        // Все ответы под /api — всегда JSON, даже без заголовка Accept
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => self::isApi($request)
        );

        // 422: ошибки валидации
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return self::json('Ошибка валидации', 422, ['errors' => $e->errors()]);
        });

        // 401: нет токена или он недействителен
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return self::json('Требуется авторизация.', 401);
        });

        // 404: запись не найдена (Route Model Binding) или маршрута нет
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            $previous = $e->getPrevious();
            $message = $previous instanceof ModelNotFoundException
                ? (self::NOT_FOUND[$previous->getModel()] ?? 'Запись не найдена.')
                : 'Маршрут не найден.';

            return self::json($message, 404);
        });

        // 403: действие запрещено (Policy, Gate)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return self::json('Доступ запрещён.', 403);
        });

        // Ошибки БД: ссылка на запись (1451) — 409, дубликат (1062) — 422
        $exceptions->render(function (QueryException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            $code = $e->errorInfo[1] ?? null;

            if ($code === 1451) {
                return self::json('Нельзя удалить запись: на неё ссылаются другие данные.', 409);
            }

            if ($code === 1062) {
                return self::json('Запись с такими данными уже существует.', 422);
            }

            return null; // остальное — в общий обработчик (500)
        });

        // Остальные HTTP-ошибки: abort(409, '...'), 405, 429 и т. д.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = in_array($status, [405, 429], true) || $e->getMessage() === ''
                ? (self::DEFAULT_MESSAGES[$status] ?? 'Ошибка запроса.')
                : $e->getMessage();

            return self::json($message, $status);
        });

        // 500: непредвиденная ошибка без стека вызовов и SQL (при APP_DEBUG=false)
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! self::isApi($request) || config('app.debug')) {
                return null;
            }

            return self::json('Внутренняя ошибка сервера.', 500);
        });
    }

    private static function isApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private static function json(string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['message' => $message], $extra), $status);
    }
}
