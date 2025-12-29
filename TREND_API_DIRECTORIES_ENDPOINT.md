# TrendAgent API: Endpoint для справочников (Directories)

## Общая информация

**Endpoint:** `https://apartment-api.trendagent.ru/v1/directories`

**Метод:** `GET`

**Описание:** Получение справочников/директорий для фильтрации и отображения данных

## Параметры запроса

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `auth_token` | string | ✅ | JWT токен авторизации |
| `city` | string | ✅ | ObjectId города (MongoDB _id) |
| `lang` | string | ❌ | Язык ответа (по умолчанию `ru`) |
| `types` | array | ✅ | Массив типов справочников для получения |

## Доступные типы справочников (types)

Можно указать несколько типов через параметр `types`:

- `subway_distances` - Расстояния до метро
- `rooms` - Типы комнат
- `balcony_types` - Типы балконов
- `banks` - Банки
- `building_types` - Типы зданий
- `cardinals` - Стороны света
- `contracts` - Типы договоров
- `deadlines` - Сроки сдачи
- `deadline_keys` - Ключи сроков сдачи
- `delta_prices` - Диапазоны изменения цен
- `region_registrations` - Регистрация в регионе
- `elevator_types` - Типы лифтов
- `escrow_banks` - Банки-эскроу
- `finishings` - Типы отделки
- `without_initial_fee` - Без первоначального взноса
- `installment_tags` - Теги рассрочки
- `level_types` - Типы уровня (Стандарт, Комфорт, Бизнес, Элитный)
- `locations` - Локации (районы, поселки, деревни и т.д.)
- `mortgage_types` - Типы ипотеки
- `nearby_place_types` - Типы близлежащих мест
- `parking_types` - Типы парковок
- `payment_types` - Типы оплаты
- `premise_types` - Типы помещений
- `regions` - Регионы (районы города)
- `sales_start` - Даты начала продаж
- `subways` - Станции метро
- `view_places` - Виды из окон
- `window_views` - Виды окон
- `window_types` - Типы окон

## Пример запроса

```
GET https://apartment-api.trendagent.ru/v1/directories
  ?types=subway_distances
  &types=rooms
  &types=balcony_types
  &types=banks
  &types=building_types
  &types=cardinals
  &types=contracts
  &types=deadlines
  &types=deadline_keys
  &types=delta_prices
  &types=region_registrations
  &types=elevator_types
  &types=escrow_banks
  &types=finishings
  &types=without_initial_fee
  &types=installment_tags
  &types=level_types
  &types=locations
  &types=mortgage_types
  &types=nearby_place_types
  &types=parking_types
  &types=payment_types
  &types=premise_types
  &types=regions
  &types=sales_start
  &types=subways
  &types=view_places
  &types=window_views
  &types=window_types
  &auth_token=eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
  &city=58c665588b6aa52311afa01b
  &lang=ru
```

## Структура ответа

Ответ содержит объект, где ключи соответствуют типам запрошенных справочников:

```json
{
  "subway_distances": [...],
  "rooms": [...],
  "regions": [...],
  "subways": [...],
  "locations": [...],
  ...
}
```

## Важные справочники для нашей БД

### 1. Regions (Регионы)

```json
{
  "_id": "5983801cd07ed144bb7cca1b",
  "guid": "admiralteyskiy",
  "crm_id": 24,
  "priority": 500,
  "name": "Адмиралтейский р-н"
}
```

**Поля:**
- `_id` - ObjectId региона (MongoDB)
- `guid` - Уникальный идентификатор (slug)
- `crm_id` - ID в CRM системе
- `priority` - Приоритет отображения
- `name` - Название региона

### 2. Locations (Локации)

```json
{
  "_id": "6793a4d9d60e3edf808703e0",
  "name": "Бегуницкое сельское поселение",
  "guid": "begunickoe-selskoe-poselenie"
}
```

**Поля:**
- `_id` - ObjectId локации (MongoDB)
- `guid` - Уникальный идентификатор (slug)
- `name` - Название локации

### 3. Subways (Метро)

```json
{
  "_id": "58c665598b6aa52311afa1e5",
  "guid": "avtovo",
  "name": "Автово",
  "line_number": 1,
  "color": "#bb2f30"
}
```

**Поля:**
- `_id` - ObjectId станции (MongoDB)
- `guid` - Уникальный идентификатор (slug)
- `name` - Название станции
- `line_number` - Номер линии
- `color` - Цвет линии

## Использование для синхронизации данных

Этот endpoint можно использовать для:

1. **Синхронизации регионов** - обновление/добавление регионов в таблицу `regions`
2. **Синхронизации локаций** - обновление/добавление локаций в таблицу `locations`
3. **Синхронизации метро** - обновление/добавление станций метро в таблицу `subways`
4. **Заполнения справочников** - для фильтров и отображения в админ-панели

## Важные особенности

1. **City параметр**: Требует ObjectId города (не GUID!), поэтому нужно использовать `external_id` из таблицы `cities`
2. **Множественные types**: Можно запросить все нужные справочники одним запросом
3. **Городозависимые данные**: Справочники зависят от города (например, метро СПб отличается от метро Москвы)
4. **Актуальность данных**: Справочники могут изменяться, рекомендуется периодическая синхронизация

## Рекомендации

1. Создать команду для синхронизации справочников: `php artisan trend:sync-directories --city=spb`
2. Создать сервис для работы с directories API
3. Использовать кэширование для справочников (они редко меняются)
4. Периодически обновлять справочники (например, раз в день или неделю)

