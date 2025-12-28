# Исправление: Использование ObjectId для blocks API

## Проблема

API блоков (`https://api.trendagent.ru/v4_29/blocks/search/`) требует MongoDB ObjectId для параметра `city`, а не GUID.

**Ошибка:**
```
"errors":{"city":{"param":"city","msg":"MongoID required","value":"spb"}}
```

## Решение

Использовать поле `external_id` города (которое содержит ObjectId) вместо `guid` для типа `blocks`.

## Изменения

**Файл:** `app/Console/Commands/ParseTrendData.php`

**Метод:** `buildParams()`

```php
// Для blocks API требует MongoDB ObjectId вместо GUID
// Используем external_id если есть, иначе guid
if ($objectType === 'blocks' && !empty($city->external_id)) {
    $params['city'] = $city->external_id;
} else {
    $params['city'] = $city->guid;
}
```

## Как заполнить external_id для городов

### Вариант 1: Из ответа API при парсинге

При первом успешном запросе к API blocks, в ответе может быть информация о городе с его `_id`. 
Этот `_id` можно использовать для обновления `external_id` города в БД.

### Вариант 2: Получить из API списка городов

Если есть endpoint для получения списка городов, можно использовать его для получения `_id` для каждого города.

### Вариант 3: Обновить вручную через админ-панель или базу данных

Можно вручную обновить поле `external_id` для каждого города в таблице `cities`.

## Временное решение

**ВАЖНО:** Если `external_id` не заполнен, будет использоваться `guid`, что приведет к ошибке 400. 

**Необходимо заполнить `external_id` для городов перед использованием парсера для blocks.**

## Проверка наличия external_id

Проверить, какие города имеют `external_id`:
```sql
SELECT id, guid, name, external_id FROM cities WHERE is_active = 1;
```

Города без `external_id` не смогут использоваться для парсинга blocks до тех пор, пока не будет заполнен `external_id`.

