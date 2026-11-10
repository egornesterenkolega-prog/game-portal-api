# Игровой портал — REST API

Серверная часть портала, на котором пользователи публикуют игры, указывают цену, ставят лайки и дизлайки, оставляют комментарии, а администраторы модерируют контент.

**Стек:** PHP 8.3, Laravel 13, MySQL 8, Laravel Sanctum (токены).
**Авторы:** Нестеренко Егор (бэкенд), Али Дамир (фронтенд), группа ИС 24-23Б.

## Быстрый старт

```bash
git clone <адрес репозитория> game-portal-api
cd game-portal-api
composer install
cp .env.example .env
php artisan key:generate
```

В `.env` укажите базу данных MySQL (создайте пустую базу `game_portal_app`):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=game_portal_app
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed      # таблицы и тестовые данные
php artisan storage:link        # доступ к загруженным скриншотам
php artisan serve               # http://127.0.0.1:8000
```

Проверка: `GET http://127.0.0.1:8000/api/health` → `{"app": "...", "time": "...", "database": "ok"}`.

## Тестовые учётные записи

Пароль у всех: `password`.

| Email                  | Роль                      |
|------------------------|---------------------------|
| admin@gameportal.test  | администратор             |
| user@gameportal.test   | обычный пользователь      |
| blocked@gameportal.test| заблокированный пользователь |

## Описание API

Базовый адрес `/api/v1`. Авторизация: заголовок `Authorization: Bearer <token>` (токен выдают `POST /register` и `POST /login`). Все ответы — JSON.

| Метод | Адрес | Роль | Описание |
|-------|-------|------|----------|
| POST | /register | гость | регистрация, выдаёт токен |
| POST | /login | гость | вход, выдаёт токен |
| POST | /logout | пользователь | выход (токен удаляется) |
| GET | /me | пользователь | текущий пользователь |
| PATCH | /me | пользователь | изменить своё имя |
| GET | /games | все | каталог: поиск, фильтры, сортировка, пагинация |
| GET | /games/{id} | все | страница игры |
| POST | /games | пользователь | опубликовать игру (multipart/form-data) |
| PUT/PATCH | /games/{id} | автор игры | изменить игру |
| DELETE | /games/{id} | автор или админ | удалить игру |
| GET | /genres, /genres/{id} | все | жанры |
| POST, PUT, DELETE | /genres, /genres/{id} | админ | управление жанрами |
| GET | /games/{id}/comments | все | комментарии игры |
| POST | /games/{id}/comments | пользователь | добавить комментарий |
| PUT | /comments/{id} | автор комментария | изменить комментарий |
| DELETE | /comments/{id} | автор или админ | удалить комментарий |
| PUT | /games/{id}/reaction | пользователь | поставить лайк/дизлайк |
| DELETE | /games/{id}/reaction | пользователь | снять оценку |
| GET | /users/{id} | все | профиль автора и его игры |
| POST | /users/{id}/block, /unblock | админ | блокировка и разблокировка |
| GET | /stats | админ | статистика портала |

Параметры каталога `GET /games`: `search`, `author`, `genre_id`, `min_rating`, `price_from`, `price_to`, `sort` (`rating`, `created_at`, `price`, `title`), `order` (`asc`/`desc`), `per_page` (1–50), `page`. Ответ содержит `data`, `links` и `meta`.

**Формат ошибок:** `{"message": "..."}`, для 422 дополнительно `{"errors": {"поле": ["сообщение"]}}`.
Коды: 401 — нет токена; 403 — нет прав или аккаунт заблокирован; 404 — не найдено; 409 — нарушено бизнес-правило; 422 — ошибка валидации.

**Бизнес-правила (409):**
1. Автор может опубликовать не более 10 игр (`PORTAL_MAX_GAMES_PER_USER`).
2. Нельзя оценивать собственную игру.
3. Не более 5 комментариев одного пользователя к одной игре (`PORTAL_MAX_COMMENTS_PER_GAME`).
4. Нельзя удалить жанр, назначенный играм.

### Документация

Полная документация (описание, роли, примеры запросов и ответов, коды ошибок для каждого запроса) — коллекция Postman: [docs/game-portal.postman_collection.json](docs/game-portal.postman_collection.json). Импорт: Postman → Import.

## Автотесты

Тесты (PHPUnit, Feature) используют отдельную БД — SQLite в памяти (`phpunit.xml`), трейт `RefreshDatabase`, фабрики и `Sanctum::actingAs()`. Рабочая база MySQL не затрагивается. Требуется включённое расширение `pdo_sqlite` (в Laragon включено по умолчанию).

```bash
php artisan test                         # все тесты
php artisan test --filter=GameManagementTest   # один класс
```

Набор тестов: `AuthApiTest`, `GameCatalogTest`, `GameManagementTest`, `ReactionAndCommentTest`, `GenreAndAdminTest`, `LoggingTest`, `HealthTest`.

## Логирование

- Middleware `App\Http\Middleware\LogApiRequests` записывает каждый запрос к API: метод, путь, id пользователя, код ответа, время выполнения, IP. Тело запроса и заголовки (пароли, токены) в журнал не попадают.
- События бизнес-логики пишутся через фасад `Log`: `info` (игра опубликована, комментарий добавлен), `warning` (нарушено бизнес-правило, неудачный вход), `error` (непредвиденный сбой публикации).
- Журнал: `storage/logs/laravel.log`. Просмотр в реальном времени: `Get-Content storage/logs/laravel.log -Wait -Tail 20` (PowerShell).
## Запуск приложения (сервер и клиент)

Приложение состоит из двух частей: API на Laravel (этот репозиторий) и клиента (каталог `client/`, отдельный адрес и порт).

### 1. Сервер Laravel
```bash
composer install
cp .env.example .env
php artisan key:generate
# в .env: DB_CONNECTION=mysql, DB_DATABASE=game_portal_app, CORS_ALLOWED_ORIGINS=<адрес клиента>
php artisan migrate:fresh --seed     # чистая БД и тестовые данные
php artisan storage:link             # доступ к загруженным скриншотам
php artisan serve                    # API: http://127.0.0.1:8000
```

### 2. Клиент
Клиент использует `API_URL` — базовый адрес API (по умолчанию `http://127.0.0.1:8000/api/v1`), он задаётся в одном месте.

- Vite (Vue/React): `cd client && npm install && npm run dev` → http://localhost:5173
- Чистый HTML/JS: открыть `client/index.html` через Live Server → http://127.0.0.1:5500

Адрес клиента должен быть указан в `CORS_ALLOWED_ORIGINS` (файл `.env` сервера); после изменения выполните `php artisan config:clear`.

### 3. Вход
Пароль у всех: `password`.

| Роль | Email |
|------|-------|
| администратор | admin@gameportal.test |
| пользователь | user@gameportal.test |
| заблокированный | blocked@gameportal.test |

### Сценарий для демонстрации
1. Регистрация нового пользователя → вход.
2. Каталог: поиск, фильтр по жанру и цене, сортировка, переключение страниц.
3. Публикация игры (название, описание, цена, 1–3 жанра, скриншоты, файл) → карточка игры.
4. Нарушение бизнес-правил: оценка своей игры, лимит игр, лимит комментариев (сообщения 409).
5. Вход под администратором: статистика, управление жанрами, блокировка пользователя из профиля автора.
