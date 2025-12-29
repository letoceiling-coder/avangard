# Данные, сохраняемые парсером для каждого типа объекта

**Дата:** 29 декабря 2025

## Общая информация

При запуске парсера для каждого типа объекта сохраняются основные данные объекта, связи с другими сущностями (город, застройщик, район, локация), изображения, а также автоматически создаются записи об источнике данных и метках времени синхронизации.

---

## 1. Блоки (Квартиры) - `blocks`

### Основные поля объекта

| Поле | Тип | Описание |
|------|-----|----------|
| `guid` | string | Уникальный идентификатор из TrendAgent (используется для поиска существующих объектов) |
| `name` | string | Название ЖК/блока |
| `address` | string | Адрес объекта (массив преобразуется в строку через запятую) |
| `external_id` | string | MongoDB ObjectId из TrendAgent API (`_id`) |
| `crm_id` | string | ID в CRM системе |
| `latitude` | float | Широта (географические координаты) |
| `longitude` | float | Долгота (географические координаты) |
| `status` | int | Статус объекта (1 = активен, другие значения = неактивен) |
| `edit_mode` | string | Режим редактирования |
| `is_suite` | boolean | Является ли блок апартаментами |
| `is_exclusive` | boolean | Эксклюзивный объект |
| `is_marked` | boolean | Помеченный объект |
| `is_active` | boolean | Активен ли объект (вычисляется из `status`) |
| `min_price` | int | Минимальная цена (в копейках) |
| `max_price` | int | Максимальная цена (в копейках) |
| `apartments_count` | int | Общее количество квартир |
| `view_apartments_count` | int | Количество видимых квартир |
| `exclusive_apartments_count` | int | Количество эксклюзивных квартир |
| `deadline` | string | Срок сдачи (текстовое значение) |
| `deadline_date` | date | Дата сдачи (парсится из API) |
| `deadline_over_check` | boolean | Проверка просрочки сдачи |
| `finishing` | string | Тип отделки |
| `data_source` | string | Источник данных (всегда `'parser'` для парсера) |
| `metadata` | json | Дополнительные метаданные из API |
| `advantages` | json | Преимущества объекта |
| `payment_types` | json | Типы платежей |
| `contract_types` | json | Типы договоров |

### Связи (Foreign Keys)

| Поле | Тип | Описание |
|------|-----|----------|
| `city_id` | int | ID города (обязательное поле) |
| `builder_id` | int | ID застройщика |
| `region_id` | int | ID района/округа |
| `location_id` | int | ID локации/микрорайона |

### Дополнительные данные

**Связь с метро (`syncBlockSubways`):**
- Создаются записи в таблице `block_subway` (pivot table)
- Сохраняется расстояние до метро, тип расстояния (пешком/транспортом), время, приоритет

**Цены (`syncBlockPrices`):**
- Заглушка для будущей синхронизации цен по типам квартир

**Изображения (`syncImages`):**
- Создаются записи в таблице `images` (полиморфная связь)
- Сохраняются: `external_id`, `file_name`, `path`, `url_thumbnail`, `url_full`, `alt`, `order`

**Метки времени:**
- `parsed_at` - время последнего парсинга
- `last_synced_at` - время последней синхронизации

**Источник данных:**
- Создается запись в `data_sources` (полиморфная связь)
- `source_type` = `'parser'`
- `source_name` = `'TrendAgent API'`
- `processed_at` = текущее время

---

## 2. Паркинги - `parkings`

**Примечание:** Используется тот же метод `syncBlock`, что и для блоков, так как структура данных похожа.

Все поля аналогичны полям блоков (см. раздел 1).

---

## 3. Поселки (Дома с участками) - `villages`

### Основные поля объекта

| Поле | Тип | Описание |
|------|-----|----------|
| `guid` | string | Уникальный идентификатор из TrendAgent |
| `name` | string | Название поселка |
| `address` | string | Адрес поселка |
| `external_id` | string | MongoDB ObjectId из API (`_id`) |
| `plots_count` | int | Общее количество участков в поселке |
| `view_plots_count` | int | Количество видимых участков |
| `distance` | float | Расстояние до центра города |
| `deadline` | string | Срок сдачи (текстовое значение) |
| `deadline_date` | date | Дата сдачи |
| `sales_start` | string | Начало продаж (текстовое значение) |
| `sales_start_date` | date | Дата начала продаж |
| `reward_label` | string | Метка награды/особого предложения |
| `is_new_village` | boolean | Новый поселок |
| `is_active` | boolean | Активен ли поселок (из `status`) |
| `data_source` | string | Источник данных (`'parser'`) |
| `metadata` | json | Дополнительные метаданные |
| `property_types` | json | Типы недвижимости |

### Связи (Foreign Keys)

| Поле | Тип | Описание |
|------|-----|----------|
| `city_id` | int | ID города (обязательное поле) |
| `builder_id` | int | ID застройщика |

### Дополнительные данные

**Изображения:**
- Аналогично блокам (см. раздел 1)

**Метки времени и источник данных:**
- Аналогично блокам

---

## 4. Участки - `plots`

### Основные поля объекта

| Поле | Тип | Описание |
|------|-----|----------|
| `guid` | string | Уникальный идентификатор из TrendAgent |
| `name` | string | Название участка |
| `address` | string | Адрес участка |
| `external_id` | string | MongoDB ObjectId из API (`_id`) |
| `crm_id` | string | ID в CRM системе |
| `latitude` | float | Широта |
| `longitude` | float | Долгота |
| `min_price` | int | Минимальная цена (в копейках) |
| `max_price` | int | Максимальная цена (в копейках) |
| `area_min` | float | Минимальная площадь участка |
| `area_max` | float | Максимальная площадь участка |
| `status` | int | Статус участка |
| `is_active` | boolean | Активен ли участок |
| `data_source` | string | Источник данных (`'parser'`) |
| `metadata` | json | Дополнительные метаданные |

### Связи (Foreign Keys)

| Поле | Тип | Описание |
|------|-----|----------|
| `city_id` | int | ID города (обязательное поле) |
| `village_id` | int | ID поселка (если участок в поселке) |
| `builder_id` | int | ID застройщика |
| `location_id` | int | ID локации |

### Дополнительные данные

**Изображения:**
- Аналогично блокам

**Метки времени и источник данных:**
- Аналогично блокам

---

## 5. Коммерческие объекты - `commercial-blocks`

### Основные поля объекта

| Поле | Тип | Описание |
|------|-----|----------|
| `guid` | string | Уникальный идентификатор из TrendAgent |
| `name` | string | Название коммерческого объекта |
| `address` | string | Адрес объекта |
| `external_id` | string | MongoDB ObjectId из API (`_id`) |
| `premises_count` | int | Общее количество помещений |
| `booked_premises_count` | int | Количество забронированных помещений |
| `is_new_block` | boolean | Новый коммерческий объект |
| `is_active` | boolean | Активен ли объект |
| `deadlines` | json | Сроки сдачи (массив) |
| `deadline_date` | date | Дата сдачи |
| `deadline_over_check` | boolean | Проверка просрочки |
| `sales_start_at` | datetime | Дата начала продаж |
| `reward_label` | string | Метка награды/особого предложения |
| `data_source` | string | Источник данных (`'parser'`) |
| `metadata` | json | Дополнительные метаданные |
| `property_types` | json | Типы недвижимости |
| `min_prices` | json | Минимальные цены (по типам помещений) |

### Связи (Foreign Keys)

| Поле | Тип | Описание |
|------|-----|----------|
| `city_id` | int | ID города (обязательное поле) |
| `builder_id` | int | ID застройщика |
| `district_id` | int | ID района/округа |
| `location_id` | int | ID локации |

### Дополнительные данные

**Изображения:**
- Аналогично блокам

**Метки времени и источник данных:**
- Аналогично блокам

---

## 6. Коммерческие помещения - `commercial-premises`

### Основные поля объекта

| Поле | Тип | Описание |
|------|-----|----------|
| `guid` | string | Уникальный идентификатор из TrendAgent |
| `name` | string | Название помещения |
| `address` | string | Адрес помещения |
| `external_id` | string | MongoDB ObjectId из API (`_id`) |
| `crm_id` | string | ID в CRM системе |
| `latitude` | float | Широта |
| `longitude` | float | Долгота |
| `price` | int | Цена помещения (в копейках) |
| `price_unit` | string | Единица измерения цены (за м², за объект и т.д.) |
| `area` | float | Площадь помещения |
| `premise_type` | string | Тип помещения (офис, магазин, склад и т.д.) |
| `property_types` | json | Типы недвижимости |
| `status` | int | Статус помещения |
| `is_active` | boolean | Активно ли помещение |
| `is_booked` | boolean | Забронировано ли помещение |
| `data_source` | string | Источник данных (`'parser'`) |
| `metadata` | json | Дополнительные метаданные |

### Связи (Foreign Keys)

| Поле | Тип | Описание |
|------|-----|----------|
| `city_id` | int | ID города (обязательное поле) |
| `commercial_block_id` | int | ID коммерческого объекта (если помещение в объекте) |
| `builder_id` | int | ID застройщика |
| `district_id` | int | ID района/округа |
| `location_id` | int | ID локации |

### Дополнительные данные

**Изображения:**
- Аналогично блокам

**История цен:**
- При изменении цены создается запись в `price_history`
- Сохраняется: `old_price`, `new_price`, `price_type` = `'single'`, `source` = `'parser'`

**Метки времени и источник данных:**
- Аналогично блокам

---

## Общие дополнительные данные для всех типов

### 1. Отслеживание изменений (`track_changes = true`)

**Таблица `data_changes`:**
- `changeable_type` - класс модели
- `changeable_id` - ID объекта
- `field_name` - название измененного поля
- `old_value` - старое значение (сериализованное)
- `new_value` - новое значение (сериализованное)
- `change_type` - тип изменения (`'price'`, `'status'`, `'important'`, `'other'`)
- `source` - источник изменения (`'parser'`)
- `changed_at` - время изменения
- `user_id` - ID пользователя (если авторизован)

**Критические поля (для блоков):**
- `min_price`, `max_price`, `status`, `is_active`, `deadline_date`

**Важные поля (для блоков):**
- `name`, `address`, `finishing`, `deadline`

### 2. История цен (`log_price_changes = true`)

**Таблица `price_history`:**
- `priceable_type` - класс модели
- `priceable_id` - ID объекта
- `price_type` - тип цены (`'min'`, `'max'`, `'single'`)
- `old_price` - старая цена
- `new_price` - новая цена
- `source` - источник изменения (`'parser'`)
- `changed_at` - время изменения
- `user_id` - ID пользователя

### 3. Изображения

**Таблица `images` (полиморфная связь):**
- `imageable_type` - класс модели
- `imageable_id` - ID объекта
- `external_id` - ID изображения в API
- `file_name` - имя файла
- `path` - путь к файлу
- `url_thumbnail` - URL миниатюры
- `url_full` - URL полноразмерного изображения
- `alt` - альтернативный текст
- `order` - порядок сортировки
- `is_valid` - валидность изображения (если включена проверка)
- `validated_at` - время проверки

### 4. Источник данных

**Таблица `data_sources` (полиморфная связь):**
- `sourceable_type` - класс модели
- `sourceable_id` - ID объекта
- `source_type` - тип источника (`'parser'`, `'manual'`, `'feed'`)
- `source_name` - название источника (`'TrendAgent API'`)
- `processed_at` - время обработки

### 5. Метки времени синхронизации

Все объекты имеют поля (наследуются от `BaseTrendModel`):
- `parsed_at` - время последнего парсинга
- `last_synced_at` - время последней синхронизации

Эти поля устанавливаются методами:
- `markAsParsed()` - устанавливает `parsed_at = now()`
- `markAsSynced()` - устанавливает `last_synced_at = now()`

---

## Автоматическое создание справочников

Если в опциях установлено `create_missing_references = true`, парсер автоматически создает отсутствующие справочники:

### Города (`findOrCreateCity`)
- `guid`, `name`, `crm_id`, `external_id`, `is_active`

### Застройщики (`findOrCreateBuilder`)
- `guid`, `name`, `crm_id`, `external_id`, `is_active`

### Районы (`findOrCreateRegion`)
- `city_id`, `guid`, `name`, `crm_id`, `external_id`, `is_active`

### Локации (`findOrCreateLocation`)
- `city_id`, `guid`, `name`, `crm_id`, `external_id`, `is_active`

**Важно:** Локации проверяются по комбинации `guid` + `city_id` (guid должен быть уникален в рамках города).

---

## Конвертация данных

### Цены
- Все цены конвертируются в **копейки** (метод `convertPriceToKopecks`)
- Если цена > 1000000, считается что уже в копейках
- Иначе умножается на 100

### Даты
- Парсятся через `Carbon::parse()` (метод `parseDate`)
- При ошибке парсинга возвращается `null`

### Адреса
- Если адрес массив, преобразуется в строку через `implode(', ', ...)`

---

## Логирование ошибок

Все ошибки парсинга логируются в таблицу `parser_errors`:
- `error_type` - тип ошибки (`'parsing'`, `'api'`, etc.)
- `object_type` - тип объекта
- `external_id`, `guid` - идентификаторы объекта
- `error_message` - сообщение об ошибке
- `error_details` - стек трейс
- `context` - контекст ошибки (JSON)
- `api_url`, `http_status_code`, `response_body` - информация об API запросе

