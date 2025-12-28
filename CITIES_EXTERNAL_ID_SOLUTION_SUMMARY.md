# Решение: Обновление external_id для городов

## Проблема

API blocks требует MongoDB ObjectId для параметра `city`, но в базе данных городов отсутствует поле `external_id` с ObjectId.

## Решение

Создана команда `cities:update-external-id`, которая автоматически получает MongoDB ObjectId для городов из API TrendAgent и обновляет поле `external_id` в базе данных.

## Созданные файлы

1. **`app/Console/Commands/UpdateCitiesExternalId.php`**
   - Команда для обновления external_id городов
   - Получает ObjectId из ответов API (parkings, villages, commercial-blocks)
   - Обновляет поле external_id в БД

2. **`UPDATE_CITIES_EXTERNAL_ID_SOLUTION.md`**
   - Документация по использованию команды

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

Команда:
1. Авторизуется через Trend SSO API
2. Для каждого города пытается получить ObjectId из разных API endpoints:
   - Parkings API (`https://parkings.trendagent.ru/search/places/`)
   - Villages API (`https://house-api.trendagent.ru/v1/search/villages`)
   - Commercial Blocks API (`https://commerce.trendagent.ru/search/blocks/`)
3. Извлекает `city._id` из ответов API
4. Обновляет поле `external_id` в таблице `cities`

## Статус

✅ Команда создана и зарегистрирована  
✅ Изменения отправлены на сервер  
⚠️ Требуется тестирование на сервере с реальным API

## Примечания

- Команда пропускает города, у которых уже есть `external_id`
- Если ObjectId не найден, город остается без изменений
- Команда логирует все операции для отладки
- Все операции безопасны и не нарушают существующие данные

## Следующие шаги

1. На сервере выполнить команду для обновления external_id:
   ```bash
   php artisan cities:update-external-id
   ```

2. Проверить результат:
   ```sql
   SELECT id, guid, name, external_id FROM cities WHERE is_active = 1;
   ```

3. После обновления external_id парсер blocks API будет работать корректно

