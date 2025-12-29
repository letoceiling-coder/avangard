# Отчет об исправлениях парсера

**Дата:** 29 декабря 2025

## Проблема

При запуске парсера через кнопку в админ-панели возникали ошибки:
- Для `blocks`: "MongoID required" - использовался `guid` (например, "mo") вместо MongoDB ObjectId
- Для других типов объектов: "Invalid ObjectId" - также использовался `guid` вместо ObjectId

## Исправления

### 1. Использование external_id для всех типов объектов

**Файл:** `app/Console/Commands/ParseTrendData.php`

**Изменение:** Метод `buildParams()` теперь использует `external_id` (MongoDB ObjectId) для всех типов объектов, а не только для `blocks`.

**Было:**
```php
if ($objectType === 'blocks' && !empty($city->external_id)) {
    $params['city'] = $city->external_id;
} else {
    $params['city'] = $city->guid;
}
```

**Стало:**
```php
if (!empty($city->external_id)) {
    // Все новые API требуют ObjectId (external_id)
    $params['city'] = $city->external_id;
} else {
    // Fallback на guid с логированием предупреждения
    Log::warning("ParseTrendData: City {$city->name} does not have external_id, using guid", ...);
    $params['city'] = $city->guid;
}
```

### 2. Фильтрация регионов

**Файл:** `app/Console/Commands/ParseTrendData.php`

**Изменение:** Метод `getCities()` теперь исключает регионы (где `region_id` IS NULL) и возвращает только города.

**Было:**
```php
return City::where('is_active', true)->get();
```

**Стало:**
```php
return City::where('is_active', true)
    ->whereNotNull('region_id') // Только города, не регионы
    ->get();
```

### 3. Фильтрация городов без external_id

**Файл:** `app/Console/Commands/ParseTrendData.php`

**Изменение:** Добавлена фильтрация городов без `external_id` после получения списка.

**Добавлено:**
```php
// Фильтруем города - используем только те, у которых есть external_id
$cities = $cities->filter(function ($city) {
    return !empty($city->external_id);
});

if ($cities->isEmpty()) {
    $this->error('❌ Не найдено активных городов с external_id для парсинга. Выполните команду cities:update-external-id');
    return 1;
}
```

## Результат

После исправлений парсер:
- ✅ Использует `external_id` (MongoDB ObjectId) для всех типов объектов
- ✅ Исключает регионы из списка городов
- ✅ Фильтрует города без `external_id`
- ✅ Логирует предупреждения при использовании `guid` вместо `external_id`

## Рекомендации

1. Убедиться, что все города имеют заполненное поле `external_id`
2. При необходимости выполнить команду `php artisan cities:update-external-id` для обновления `external_id`
3. Проверить логи на наличие предупреждений о городах без `external_id`

