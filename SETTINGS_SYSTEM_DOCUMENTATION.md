# Система настроек TrendAgent API

## Обзор

Создана система настроек для хранения и управления учетными данными TrendAgent API (телефон и пароль) с возможностью редактирования через админ-панель.

## Структура

### 1. База данных

**Таблица:** `settings`

Поля:
- `id` - ID записи
- `key` - Уникальный ключ настройки (например, `trend.phone`)
- `value` - Значение настройки
- `type` - Тип значения (`string`, `integer`, `boolean`, `json`)
- `description` - Описание настройки
- `group` - Группа настроек (`trend`, `general`, и т.д.)
- `created_at`, `updated_at` - Метки времени

**Настройки по умолчанию:**
- `trend.phone` = `+79045393434`
- `trend.password` = `nwBvh4q`

### 2. Модель Setting

**Файл:** `app/Models/Setting.php`

**Основные методы:**
- `Setting::get($key, $default = null)` - Получить значение настройки
- `Setting::set($key, $value, $type, $description, $group)` - Установить настройку
- `Setting::getByGroup($group)` - Получить все настройки группы

### 3. Helper TrendSettings

**Файл:** `app/Helpers/TrendSettings.php`

**Методы:**
- `TrendSettings::getPhone()` - Получить телефон (из настроек или по умолчанию)
- `TrendSettings::getPassword()` - Получить пароль (из настроек или по умолчанию)
- `TrendSettings::getCredentials()` - Получить оба значения

**Значения по умолчанию:**
- Телефон: `+79045393434`
- Пароль: `nwBvh4q`

### 4. API Endpoints

**Базовый путь:** `/api/v1/settings`

- `GET /api/v1/settings` - Получить все настройки (опционально: `?group=trend`)
- `GET /api/v1/settings/trend` - Получить настройки группы TrendAgent
- `PUT /api/v1/settings/trend` - Обновить настройки TrendAgent
- `GET /api/v1/settings/{key}` - Получить конкретную настройку
- `PUT /api/v1/settings/{key}` - Обновить конкретную настройку

**Контроллер:** `App\Http\Controllers\Api\SettingsController`

### 5. Админ-панель

**Страница:** `/admin/settings`

**Компонент:** `resources/js/pages/admin/Settings.vue`

**Возможности:**
- Просмотр текущих настроек TrendAgent
- Редактирование телефона и пароля
- Сохранение изменений
- Кнопка "Отменить" для возврата к исходным значениям
- Скрытие/показ пароля

## Использование в коде

### В командах Artisan

```php
use App\Helpers\TrendSettings;

// Получить телефон (из настроек или по умолчанию)
$phone = TrendSettings::getPhone();

// Получить пароль (из настроек или по умолчанию)
$password = TrendSettings::getPassword();

// Получить оба значения
$credentials = TrendSettings::getCredentials();
// ['phone' => '+79045393434', 'password' => 'nwBvh4q']
```

### В сервисах

```php
use App\Helpers\TrendSettings;

// Авторизация с использованием настроек
$authService = new TrendSsoApiAuth();
$credentials = TrendSettings::getCredentials();
$authData = $authService->authenticate($credentials['phone'], $credentials['password']);
```

### Напрямую через модель Setting

```php
use App\Models\Setting;

// Получить настройку
$phone = Setting::get('trend.phone', '+79045393434');

// Установить настройку
Setting::set('trend.phone', '+79991234567', 'string', 'Телефон для авторизации', 'trend');

// Получить все настройки группы
$trendSettings = Setting::getByGroup('trend');
```

## Обновленные команды

Все команды теперь используют настройки по умолчанию:

### 1. `trend:parse`
- Использует `TrendSettings::getPhone()` и `TrendSettings::getPassword()`
- Опции `--phone` и `--password` остаются опциональными
- Если опции не указаны, используются настройки из БД или значения по умолчанию

### 2. `trend:sync-directories`
- Использует `TrendSettings::getPhone()` и `TrendSettings::getPassword()`
- Опции `--phone` и `--password` остаются опциональными

### 3. `cities:update-external-id`
- Использует `TrendSettings::getPhone()` и `TrendSettings::getPassword()`
- Опции `--phone` и `--password` остаются опциональными

## Установка

1. **Выполнить миграцию:**
```bash
php artisan migrate
```

2. **Проверить настройки:**
```bash
php artisan tinker
>>> App\Models\Setting::get('trend.phone')
=> "+79045393434"
>>> App\Models\Setting::get('trend.password')
=> "nwBvh4q"
```

3. **Использовать в коде:**
```php
use App\Helpers\TrendSettings;
$phone = TrendSettings::getPhone();
$password = TrendSettings::getPassword();
```

## Изменение настроек

### Через админ-панель

1. Перейти на страницу `/admin/settings`
2. Изменить телефон и/или пароль
3. Нажать "Сохранить настройки"

### Через API

```bash
curl -X PUT https://your-domain.com/api/v1/settings/trend \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+79991234567",
    "password": "new_password"
  }'
```

### Через код

```php
use App\Models\Setting;

Setting::set('trend.phone', '+79991234567', 'string', 'Телефон для авторизации в TrendAgent API', 'trend');
Setting::set('trend.password', 'new_password', 'string', 'Пароль для авторизации в TrendAgent API', 'trend');
```

## Приоритет значений

1. **Опции командной строки** (`--phone`, `--password`) - наивысший приоритет
2. **Настройки из БД** (таблица `settings`)
3. **Значения по умолчанию** (в `TrendSettings`)

## Безопасность

⚠️ **Важно:** Пароли хранятся в открытом виде в базе данных. Рекомендуется:
- Ограничить доступ к таблице `settings`
- Не логировать значения паролей
- Использовать защищенные соединения для доступа к БД
- В будущем можно добавить шифрование паролей

## Расширение

Система настроек легко расширяется для других параметров:

```php
// Добавить новую настройку
Setting::set('parser.interval', '3600', 'integer', 'Интервал парсинга в секундах', 'parser');

// Использовать в коде
$interval = Setting::get('parser.interval', 3600);
```

