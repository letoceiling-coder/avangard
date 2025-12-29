# Обход проверки подписки для локальной разработки

**Дата:** 29 декабря 2025

## Изменения

Добавлен обход проверки подписки для локальной разработки в двух местах:

### 1. Middleware `CheckSubscription`

В файле `app/Http/Middleware/CheckSubscription.php` добавлена проверка окружения:

```php
// Пропускаем проверку подписки в локальной разработке
if (app()->environment('local')) {
    return $next($request);
}
```

### 2. API Controller `SubscriptionCheckController`

В файле `app/Http/Controllers/Api/SubscriptionCheckController.php` добавлен возврат успешной подписки для локальной разработки:

```php
// В локальной разработке всегда возвращаем успешную подписку
if (app()->environment('local')) {
    return response()->json([
        'success' => true,
        'is_active' => true,
        'subscription' => [
            'status' => 'active',
            'expires_at' => now()->addYear()->toDateTimeString(),
            'domain' => request()->getHost(),
        ],
        'is_expiring_soon' => false,
        'days_until_expiry' => 365,
    ]);
}
```

## Как работает

- В локальной разработке (когда `APP_ENV=local`) проверка подписки полностью пропускается
- Middleware не блокирует доступ к `/admin`
- API endpoint `/api/subscription/check` всегда возвращает успешную подписку
- Frontend код не требует изменений, так как получает корректный ответ от API

## Проверка

Убедитесь, что в файле `.env` установлено:

```
APP_ENV=local
```

После этого доступ к `/admin` будет работать без проверки подписки в локальной разработке.

