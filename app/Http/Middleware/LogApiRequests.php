<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Журнал запросов к API: метод, путь, id пользователя, код ответа, время выполнения.
 *
 * Тело запроса и заголовки (в том числе Authorization с токеном) в журнал НЕ пишутся,
 * поэтому пароли и токены в нём появиться не могут. Из строки запроса дополнительно
 * удаляются поля с чувствительными именами.
 */
class LogApiRequests
{
    /** Параметры строки запроса, значения которых нельзя записывать в журнал. */
    private const SENSITIVE = ['password', 'password_confirmation', 'token', 'access_token', 'api_key'];

    public function handle(Request $request, Closure $next): Response
    {
        // middleware глобальный (чтобы попадали и ответы 401/404), логируем только API
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $start = microtime(true);

        $response = $next($request);

        $status = $response->getStatusCode();
        $context = [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'query' => Arr::except($request->query(), self::SENSITIVE),
            'user_id' => $request->user('sanctum')?->id,
            'status' => $status,
            'duration_ms' => round((microtime(true) - $start) * 1000, 1),
            'ip' => $request->ip(),
        ];

        // 5xx — ошибка сервера, 4xx — ошибка клиента (предупреждение), остальное — информация
        match (true) {
            $status >= 500 => Log::error('API request', $context),
            $status >= 400 => Log::warning('API request', $context),
            default => Log::info('API request', $context),
        };

        return $response;
    }
}
