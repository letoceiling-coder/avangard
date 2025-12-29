# Обновление external_id городов из blocks API

## Решение

Команда `cities:update-external-id` теперь использует новый подход:

1. **Использует blocks API с известным ObjectId** (например, Москвы `5a5cb42159042faa9a218d04`)
2. **Извлекает ObjectId других городов** из ответа API
3. **Обновляет external_id** для найденных городов

## Как работает

### Шаг 1: Поиск города с известным external_id

Команда находит город, у которого уже есть `external_id` (например, Москва после выполнения seeder).

### Шаг 2: Запрос к blocks API

Делает запрос к blocks API с использованием известного ObjectId:

```
GET https://api.trendagent.ru/v4_29/blocks/search/
  ?city=5a5cb42159042faa9a218d04  (ObjectId Москвы)
  &lang=ru
  &count=100
  &show_type=list
  &sort=id
  &sort_order=desc
```

### Шаг 3: Извлечение ObjectId из ответа

В ответе API каждый блок содержит информацию о городе:

```json
{
  "data": {
    "results": [
      {
        "city": {
          "_id": "5a5cb42159042faa9a218d04",
          "guid": "msk",
          "name": "Москва",
          "crm_id": 1
        },
        ...
      }
    ]
  }
}
```

Команда извлекает ObjectId (`_id`) для каждого найденного города.

### Шаг 4: Обновление external_id

Если найден город с нужным `guid`, его `external_id` обновляется в базе данных.

## Использование

```bash
php artisan cities:update-external-id
```

Команда автоматически:
1. Найдет город с external_id (например, Москву)
2. Сделает запрос к blocks API
3. Извлечет ObjectId для других городов из ответа
4. Обновит external_id в БД

## Ограничения

- Команда может найти только те города, которые присутствуют в ответе blocks API
- Если в ответе blocks API нет данных о других городах, ObjectId не будет найден
- Для получения ObjectId всех городов может потребоваться несколько запросов или ручное обновление через seeder

## Альтернатива: Seeder

Если автоматическое получение не работает, используйте seeder с известными ObjectId:

```bash
php artisan db:seed --class=UpdateCitiesExternalIdSeeder
```

