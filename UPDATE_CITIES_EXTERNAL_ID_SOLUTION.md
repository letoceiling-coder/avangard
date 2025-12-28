# Решение: Обновление external_id для городов

## Проблема

API blocks требует MongoDB ObjectId для параметра `city`, но в базе данных городов отсутствует поле `external_id` с ObjectId.

## Решение

Создана команда `cities:update-external-id`, которая:
1. Подключается к API TrendAgent
2. Пытается получить ObjectId городов из ответов API (parkings, villages, commercial-blocks)
3. Обновляет поле `external_id` для городов в БД

## Использование

### Обновить все активные города:
```bash
php artisan cities:update-external-id
```

### Обновить конкретные города:
```bash
php artisan cities:update-external-id --city=msk --city=spb
```

### С кастомными credentials:
```bash
php artisan cities:update-external-id --phone=+79991234567 --password=your_password
```

## Как работает

Команда пытается получить ObjectId из разных API endpoints:
1. Parkings API (`https://parkings.trendagent.ru/search/places/`)
2. Villages API (`https://house-api.trendagent.ru/v1/search/villages`)
3. Commercial Blocks API (`https://commerce.trendagent.ru/search/blocks/`)

Эти API работают с guid городов и в ответе могут содержать информацию о городе с его `_id`.

## Альтернативное решение (ручное)

Если автоматическое получение не работает, можно обновить external_id вручную:

### Через SQL:
```sql
UPDATE cities SET external_id = '507f1f77bcf86cd799439011' WHERE guid = 'msk';
```

Где `507f1f77bcf86cd799439011` - это MongoDB ObjectId города, полученный из API TrendAgent.

### Через админ-панель:

Добавить возможность редактирования `external_id` в админ-панели для городов.

## Примечания

- Команда пропускает города, у которых уже есть `external_id`
- Если ObjectId не найден, город остается без изменений
- Команда логирует все операции для отладки

