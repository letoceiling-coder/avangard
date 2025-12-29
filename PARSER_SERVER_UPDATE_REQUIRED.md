# Требуется обновление кода на сервере

**Дата:** 29 декабря 2025

## Проблема

На сервере все еще возникают ошибки "Array to string conversion" при парсинге:
- `parkings` - ошибки с JSON полями (metadata, advantages, payment_types, contract_types)
- `villages` - ошибка с полем `distance` 
- `commercial-premises` - ошибка с полем `property_types`

## Решение

Код уже исправлен локально и отправлен в git. Требуется:

1. **Обновить код на сервере:**
   ```bash
   git pull origin main
   ```

2. **Выполнить миграцию:**
   ```bash
   php artisan migrate
   ```
   Эта миграция изменит уникальный индекс для таблицы `locations` с `guid` на `city_id+guid`.

3. **Очистить кэш:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan optimize:clear
   ```

## Что было исправлено

1. **Метод `serializeJsonField()`**: Теперь возвращает массивы/объекты как есть (не JSON строки), так как Laravel автоматически сериализует их для полей с cast 'array'.

2. **Поле `distance` для villages**: Использует `serializeJsonField()` для корректной обработки.

3. **Все JSON поля**: Используют `serializeJsonField()` для правильной обработки массивов.

4. **Миграция locations**: Изменен уникальный индекс для поддержки одинаковых guid в разных городах.

## Проверка после обновления

После обновления кода и миграции, запустите тестовый парсер:

```bash
php artisan trend:parse --type=blocks --limit=10 --city=msk
```

Ошибки должны исчезнуть.

