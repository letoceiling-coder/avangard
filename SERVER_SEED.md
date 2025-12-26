# Команда server-seed

Команда для выполнения seeders на удаленном сервере через HTTP запрос.

## Использование

### Выполнить конкретный seeder:

```bash
php artisan server-seed --class=RoleSeeder
```

### Выполнить все seeders:

```bash
php artisan server-seed --all
```

### С отключением проверки SSL (для разработки):

```bash
php artisan server-seed --class=RoleSeeder --insecure
```

## Требования

В файле `.env` должны быть настроены:

```
DEPLOY_SERVER_URL=https://your-server.com
DEPLOY_TOKEN=your-secret-token
```

## Примеры использования

### Выполнить RoleSeeder:

```bash
php artisan server-seed --class=RoleSeeder
```

### Выполнить все seeders через DatabaseSeeder:

```bash
php artisan server-seed --all
```

### Выполнить RoleSeeder:

```bash
php artisan server-seed --class=RoleSeeder
```

## Безопасность

- Запросы защищены токеном (`DEPLOY_TOKEN`)
- Токен передается в заголовке `Authorization: Bearer <token>`
- Endpoint защищен middleware `deploy.token`

## API Endpoint

На сервере команда отправляет POST запрос на:
```
POST /api/seed
```

С заголовками:
- `Authorization: Bearer <DEPLOY_TOKEN>`
- `Content-Type: application/json`

Параметры запроса:
- `class` (string, optional) - имя конкретного seeder класса
- `all` (boolean, optional) - выполнить все seeders

## Ответ сервера

Успешный ответ:
```json
{
  "success": true,
  "message": "Все seeders выполнены успешно (2)",
  "data": {
    "status": "success",
    "total": 2,
    "success": 2,
    "failed": 0,
    "results": {
      "RoleSeeder": "success",
      "RoleSeeder": "success"
    },
    "php_version": "8.2.15",
    "php_path": "/usr/bin/php",
    "executed_at": "2025-01-15 12:00:00",
    "duration_seconds": 5.23
  }
}
```

Ошибка:
```json
{
  "success": false,
  "message": "Ошибка выполнения seeder",
  "data": {
    "status": "error",
    "error": "Class 'RoleSeeder' not found",
    "php_version": "8.2.15",
    "php_path": "/usr/bin/php",
    "executed_at": "2025-01-15 12:00:00",
    "duration_seconds": 1.23
  }
}
```

## Доступные seeders

- `DatabaseSeeder` - все seeders (используется с `--all`)
- `RoleSeeder` - роли пользователей

## Логирование

Все операции логируются на сервере в Laravel log:
- Начало выполнения
- Результаты каждого seeder
- Ошибки и исключения
- Время выполнения

