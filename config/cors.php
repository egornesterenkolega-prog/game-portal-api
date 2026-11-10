<?php

/*
| Настройки CORS (Cross-Origin Resource Sharing).
| Клиентское приложение работает на другом адресе и порту, поэтому браузер разрешит
| ему обращаться к API только если сервер вернёт заголовки Access-Control-Allow-*.
| Список разрешённых адресов клиента задаётся в .env (CORS_ALLOWED_ORIGINS, через запятую).
| Значение «*» (разрешить всем) в итоговой версии не используется.
*/

return [

    // CORS действует только для маршрутов API
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Адреса клиента: Vite (5173) и Live Server (5500), localhost и 127.0.0.1 — разные источники
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173'))
    ))),

    'allowed_origins_patterns' => [],

    // Authorization нужен для Bearer-токена; Content-Type — для JSON
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    // сколько секунд браузер может не повторять preflight-запрос
    'max_age' => 3600,

    // Авторизация по Bearer-токену, cookie не используются
    'supports_credentials' => false,

];
