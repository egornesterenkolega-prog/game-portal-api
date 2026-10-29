<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Заблокированный пользователь не может выполнять действия, требующие входа (403). */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_blocked) {
            abort(403, 'Ваш аккаунт заблокирован.');
        }

        return $next($request);
    }
}
