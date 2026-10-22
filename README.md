# Игровой портал — REST API (Laravel)

Серверная часть игрового портала: пользователи публикуют свои игры, ставят цену, оценивают игры (лайк/дизлайк) и комментируют их. Администраторы модерируют контент.

Курсовой проект. Бэкенд: Нестеренко Егор. Фронтенд: Али Дамир. Группа ИС 24-23Б.

## Стек

PHP 8.3+, Laravel, MySQL 8, Laravel Sanctum (токены), Eloquent ORM.

## Требования

- PHP 8.3 или выше с расширениями: pdo_mysql, mbstring, openssl, fileinfo, curl
- Composer
- MySQL 8
- Git

## Установка

```bash
git clone <ссылка на репозиторий> game-portal
cd game-portal
composer install
cp .env.example .env          # в Windows: copy .env.example .env
php artisan key:generate
```

Создайте базу данных `game_portal` (кодировка utf8mb4) и укажите в `.env`
параметры подключения: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

```bash
php artisan migrate --seed
php artisan serve
```

Приложение будет доступно по адресу http://127.0.0.1:8000.

## Проверка

```bash
curl http://127.0.0.1:8000/api/health
```

Ожидаемый ответ (код 200):

```json
{"app":"Игровой портал","time":"2026-10-22T15:30:00+05:00","database":"ok"}
```

Если база данных недоступна, возвращается код 503 и `"database":"error"`.

Список маршрутов: `php artisan route:list`.

## Структура собственного кода

- `app/Services` — бизнес-логика
- `app/Enums` — перечисления (роли, тип оценки)
- `app/Http/Controllers`, `app/Http/Requests`, `app/Http/Resources` — контроллеры, валидация, API Resources

## Документация API

Будет доступна по адресу `/docs` (Scribe).
