# Ручное обновление external_id для городов

## Проблема

API TrendAgent требует MongoDB ObjectId для параметра `city`, но автоматическое получение ObjectId из API не работает, так как все API endpoints требуют ObjectId для запроса.

## Решение: Ручное обновление

### Вариант 1: Использовать Seeder с известными ObjectId

1. Обновить файл `database/seeders/UpdateCitiesExternalIdSeeder.php` с известными ObjectId
2. Выполнить seeder:
   ```bash
   php artisan db:seed --class=UpdateCitiesExternalIdSeeder
   ```

### Вариант 2: Обновить через SQL

1. Подключиться к базе данных
2. Выполнить SQL запросы для каждого города:

```sql
-- Москва
UPDATE cities SET external_id = '5a5cb42159042faa9a218d04' WHERE guid = 'msk';

-- Санкт-Петербург (нужно получить ObjectId)
-- UPDATE cities SET external_id = 'OBJECT_ID_HERE' WHERE guid = 'spb';

-- Ростов-на-Дону (нужно получить ObjectId)
-- UPDATE cities SET external_id = 'OBJECT_ID_HERE' WHERE guid = 'rostov';

-- И так далее для остальных городов
```

### Вариант 3: Получить ObjectId из ответа API blocks

Если есть доступ к админ-панели TrendAgent или можно сделать запрос к blocks API с ObjectId (например, для Москвы):

1. Сделать запрос к blocks API с известным ObjectId (например, для Москвы `5a5cb42159042faa9a218d04`)
2. В ответе найти информацию о других городах с их ObjectId
3. Обновить external_id для найденных городов

### Вариант 4: Использовать команду с параметром

Можно расширить команду `cities:update-external-id` для приема внешних ObjectId:

```bash
# Пока не реализовано, но можно добавить:
php artisan cities:update-external-id --city=msk --external-id=5a5cb42159042faa9a218d04
```

## Известные ObjectId

### Москва
- ObjectId: `5a5cb42159042faa9a218d04`
- Источник: Документация TREND_API_DATA_STRUCTURES.md

### Остальные города
Нужно получить ObjectId из:
- Админ-панели TrendAgent
- Ответов API при успешных запросах
- Документации API TrendAgent

## Проверка результата

После обновления проверить:

```sql
SELECT id, guid, name, external_id 
FROM cities 
WHERE is_active = 1 
  AND region_id IS NOT NULL
ORDER BY name;
```

Города с заполненным `external_id` смогут использоваться для парсинга blocks API.

