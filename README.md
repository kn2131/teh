# Task API (Laravel 10 + Sanctum)

RESTful API для управления задачами. Аутентификация — токены Laravel Sanctum.

## Установка

Требования: PHP 8.1+, Composer, PostgreSQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

В `.env` укажите подключение к PostgreSQL (пример значений уже есть в `.env.example`).

## Создание пользователя (для логина)

Эндпоинта регистрации нет, поэтому пользователя можно создать через tinker:

```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'User', 'email' => 'user@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('secret')]);
```

## Аутентификация

`POST /api/login`

```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

Ответ:

```json
{
  "token": "abc123..."
}
```

`POST /api/logout`

Требуется заголовок: `Authorization: Bearer <token>`

Ответ:

```json
{
  "message": "Logged out"
}
```

## Задачи (требуют авторизации)

Заголовок: `Authorization: Bearer <token>`

### `GET /api/tasks`
Пагинированный список задач текущего пользователя.

### `GET /api/tasks/{id}`
Получить задачу (доступ только владельцу).

### `POST /api/tasks`
Создать задачу (отправляется email-уведомление о создании).

```json
{
  "title": "Новая задача",
  "description": "Детали задачи",
  "status": "pending",
  "due_date": "2024-12-31"
}
```

Ответ: JSON задачи + заголовок `Location`.

### `PUT/PATCH /api/tasks/{id}`
Обновить задачу (поля опциональны).

### `DELETE /api/tasks/{id}`
Удалить задачу (204 No Content).

## Проверка просроченных задач

Если задача не была завершена в срок `due_date`, отправляется email-уведомление (один раз на задачу).

Команда:

```bash
php artisan tasks:notify-overdue
```

Команда добавлена в планировщик Laravel и запускается ежедневно. Для продакшена нужен cron:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Тесты

```bash
php artisan test
```
