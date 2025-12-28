# План реализации парсера TrendAgent с сохранением в БД

## Цель проекта
Реализовать полноценный парсер данных из TrendAgent API с сохранением в БД для всех активных городов, с поддержкой различных типов объектов и проверкой актуальности данных.

## Объем задач
- Парсинг по всем активным городам из `/admin/regions`
- Типы объектов: Квартиры, Паркинги, Дома с участками, Участки, Подрядчики, Коммерция
- Проверка доступности фото (строго)
- Сохранение в БД с отслеживанием изменений
- Административный интерфейс для просмотра, редактирования, удаления
- Проверка актуальности данных
- Создание новых записей вручную
- Настройка расписания парсинга с диапазоном времени

---

## Этап 1: Анализ текущей архитектуры

### 1.1 Существующие компоненты
- ✅ `TrendSsoApiAuth` - авторизация через SSO API
- ✅ `TrendDataSyncService` - синхронизация данных Block/Parking
- ✅ Модели: `Block`, `Parking`, `Village`, `CommercialBlock`, `Builder`, `City`, `Region`
- ✅ `BaseTrendModel` - базовая модель с поддержкой изображений, изменений, истории цен
- ✅ `ParserError` - логирование ошибок парсинга
- ✅ `DataChange`, `PriceHistory` - отслеживание изменений
- ✅ API Resources для отображения данных

### 1.2 Что нужно добавить/расширить
- Расширить `TrendDataSyncService` для всех типов объектов
- Создать Artisan команду для запуска парсера
- Добавить проверку доступности фото
- Создать административные страницы для управления данными
- Реализовать планировщик задач (cron/scheduler)

---

## Этап 2: Расширение сервиса синхронизации данных

### 2.1 Добавление методов для всех типов объектов
- `syncVillage()` - синхронизация поселков (дома с участками)
- `syncPlot()` - синхронизация участков
- `syncCommercialBlock()` - синхронизация коммерческих объектов
- `syncCommercialPremise()` - синхронизация коммерческих помещений
- `syncBuilder()` - синхронизация подрядчиков

### 2.2 Проверка доступности фото
- Создать сервис `ImageValidationService`
- Проверка доступности URL фото через HTTP HEAD/GET запросы
- Сохранение статуса доступности фото в БД
- Повторная проверка недоступных фото

---

## Этап 3: Artisan команда для парсинга

### 3.1 Создание команды `ParseTrendData`
```php
php artisan trend:parse
    --cities=1,2,3        # Список ID городов (по умолчанию все активные)
    --types=blocks,parkings,villages  # Типы объектов для парсинга
    --check-images        # Проверить доступность фото
    --force               # Принудительное обновление
```

### 3.2 Логика работы команды
1. Получить список активных городов из таблицы `cities`
2. Для каждого города выполнить парсинг указанных типов объектов
3. Сохранить данные через `TrendDataSyncService`
4. Проверить доступность фото (если указано)
5. Логировать ошибки в `parser_errors`
6. Вывести статистику результатов

---

## Этап 4: Административный интерфейс

### 4.1 Пункты меню
- "Объекты Trend" (главный пункт)
  - "Блоки (Квартиры)"
  - "Паркинги"
  - "Поселки (Дома с участками)"
  - "Участки"
  - "Коммерция"
  - "Подрядчики"

### 4.2 Страницы списка объектов
Для каждого типа объектов:
- Таблица со списком (пагинация, фильтры)
- Поиск по названию, адресу, GUID
- Фильтры: город, регион, статус, источник данных
- Сортировка по дате обновления, цене, названию
- Массовые действия (активировать/деактивировать, удалить)

### 4.3 Страница редактирования объекта
- Форма редактирования всех полей
- Галерея изображений с возможностью добавления/удаления
- История изменений (DataChange)
- История цен (PriceHistory)
- Информация об источнике данных и дате последней синхронизации
- Кнопка "Проверить актуальность" (повторный запрос к API)

---

## Этап 5: Планировщик задач

### 5.1 Настройка расписания
- Создать миграцию для таблицы `parser_schedules`
- Поля: `object_type`, `city_ids`, `time_from`, `time_to`, `days_of_week`, `is_active`
- Возможность настройки через админ-панель

### 5.2 Реализация планировщика
- Создать Artisan команду `ParserScheduler`
- Проверка расписания через Laravel Scheduler (cron)
- Запуск парсера в указанный диапазон времени
- Логирование запусков

---

## Этап 6: Проверка актуальности данных

### 6.1 Методы проверки
- `checkDataActuality($object)` - проверка конкретного объекта
- `checkBatchActuality($type, $cityId)` - массовая проверка
- Сравнение данных из API с данными в БД
- Обновление при обнаружении изменений

### 6.2 Интерфейс проверки
- Кнопка "Проверить актуальность" на странице объекта
- Массовая проверка из списка объектов
- Отображение статуса актуальности (актуально/устарело)

---

## Этап 7: Создание объектов вручную

### 7.1 Форма создания
- Возможность создать объект вручную через админ-панель
- Те же поля, что и при парсинге
- Автоматическая пометка `data_source = 'manual'`
- Валидация полей

### 7.2 Редактирование объектов
- Редактирование всех полей
- Изменение источника данных запрещено (для сохранения истории)
- Сохранение изменений в `data_changes`

---

## Этап 8: Дополнительные функции

### 8.1 Статистика и отчеты
- Количество объектов по типам и городам
- Статистика ошибок парсинга
- График обновлений по времени
- Отчет по устаревшим данным

### 8.2 Уведомления
- Уведомления об ошибках парсинга
- Уведомления об устаревших данных
- Уведомления о критических изменениях (цена, статус)

---

## Порядок реализации (приоритет)

1. **Этап 2** - Расширение `TrendDataSyncService` (базовая функциональность)
2. **Этап 3** - Artisan команда для парсинга (основной функционал)
3. **Этап 2.2** - Проверка доступности фото (критично)
4. **Этап 4** - Административный интерфейс (удобство использования)
5. **Этап 6** - Проверка актуальности (мониторинг)
6. **Этап 5** - Планировщик задач (автоматизация)
7. **Этап 7** - Создание вручную (дополнительно)
8. **Этап 8** - Статистика и отчеты (опционально)

---

## Дополнительные детали реализации

### Структура изображений

**Таблица `images`:**
- `id` - ID записи
- `imageable_type` - тип объекта (App\Models\Trend\Block, Parking, Village и т.д.)
- `imageable_id` - ID объекта
- `external_id` - ID из TrendAgent API
- `file_name` - имя файла
- `path` - путь на CDN
- `url_thumbnail` - URL миниатюры
- `url_full` - URL полного изображения
- `is_main` - главное изображение
- `sort_order` - порядок сортировки
- `width`, `height`, `size`, `mime_type` - метаданные
- `is_available` - **НОВОЕ**: доступность фото (true/false)
- `checked_at` - **НОВОЕ**: дата последней проверки
- `last_error` - **НОВОЕ**: последняя ошибка при проверке

**Миграция для добавления полей проверки фото:**
```php
Schema::table('images', function (Blueprint $table) {
    $table->boolean('is_available')->default(true)->after('is_main');
    $table->timestamp('checked_at')->nullable()->after('is_available');
    $table->text('last_error')->nullable()->after('checked_at');
    $table->index(['is_available', 'checked_at']);
});
```

### Логика синхронизации изображений

**Текущая логика (в TrendDataSyncService):**
1. Получение массива изображений из API данных
2. Поиск существующих изображений по `external_id`
3. Создание/обновление записей в таблице `images`
4. Удаление изображений, которых нет в API данных

**Расширенная логика (с проверкой доступности):**
1. Выполнить текущую логику
2. Если `$options['check_images'] === true`:
   - Для каждого изображения вызвать `ImageValidationService::validateImage()`
   - Обновить `is_available`, `checked_at`, `last_error`
   - Логировать недоступные изображения
3. Если `$options['remove_unavailable'] === true`:
   - Пометить недоступные изображения как удаленные (soft delete)

---

### Структура API эндпоинтов TrendAgent

**Базовые эндпоинты:**
- `https://api.trendagent.ru/v4_29/` - основной API
- `https://parkings.trendagent.ru/` - паркинги
- `https://apartment-api.trendagent.ru/v1/` - квартиры
- `https://house-api.trendagent.ru/v1/` - дома/поселки/участки
- `https://commerce.trendagent.ru/` - коммерция

**Типы объектов и их эндпоинты:**

1. **Блоки (Квартиры)** - `syncBlock()`
   - Поиск: `https://api.trendagent.ru/v4_29/blocks/search/`
   - Параметры: `city`, `lang`, `count`, `offset`, `sort`, `sort_order`, `show_type`, `room`

2. **Паркинги** - `syncParking()` (существующий)
   - Поиск: `https://parkings.trendagent.ru/search/places/`
   - Параметры: `city`, `lang`, `count`, `auth_token`

3. **Поселки (Дома с участками)** - `syncVillage()` (нужно создать)
   - Поиск: `https://house-api.trendagent.ru/v1/search/villages`
   - Параметры: `city`, `lang`, `count`, `sort_type`, `sort_order`

4. **Участки** - `syncPlot()` (нужно создать модель и метод)
   - Фильтр: `https://house-api.trendagent.ru/v1/filter/plots`
   - Нужно найти эндпоинт для получения списка участков

5. **Коммерческие объекты** - `syncCommercialBlock()` (нужно создать)
   - Поиск: `https://commerce.trendagent.ru/search/blocks/`
   - Параметры: `city`, `lang`, `count`, `show_type`, `sort`, `sort_order`

6. **Коммерческие помещения** - `syncCommercialPremise()` (нужно создать)
   - Поиск: `https://commerce.trendagent.ru/search/premises`
   - Параметры: `city`, `lang`, `count`

7. **Подрядчики** - `syncBuilder()` (если есть API эндпоинт)
   - Нужно определить эндпоинт для получения списка подрядчиков

---

### Планирование задач (Cron/Scheduler)

**Таблица `parser_schedules`:**
```sql
CREATE TABLE parser_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_type VARCHAR(50) NOT NULL, -- blocks, parkings, villages, plots, commercial-blocks, commercial-premises, builders
    city_ids JSON NULL, -- [1,2,3] или NULL для всех активных
    time_from TIME NOT NULL, -- 00:00:00
    time_to TIME NOT NULL, -- 23:59:59
    days_of_week JSON NULL, -- [1,2,3,4,5] (1=Понедельник) или NULL для всех дней
    is_active BOOLEAN DEFAULT TRUE,
    check_images BOOLEAN DEFAULT FALSE,
    force_update BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Laravel Scheduler (app/Console/Kernel.php):**
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('trend:parse-scheduler')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
```

**Команда `trend:parse-scheduler`:**
1. Получить все активные расписания (`is_active = true`)
2. Для каждого расписания:
   - Проверить, находится ли текущее время в диапазоне `time_from` - `time_to`
   - Проверить, соответствует ли текущий день недели `days_of_week`
   - Если да - запустить `trend:parse` с параметрами из расписания

---

---

## Проверка соответствия заданию

### ✅ Требования задания покрыты планом:

1. **✅ Парсер по городам из /admin/regions**
   - План: Этап 3, пункт 3.2 - получение активных городов из таблицы `cities` (is_active = true)
   - Детали: Использование `City::active()->get()` для получения только активных городов

2. **✅ Типы объектов: Квартиры, паркинги, дома с участками, участки, подрядчики, коммерция**
   - План: Этап 2.1 - все типы объектов включены:
     - Квартиры (блоки) - `syncBlock()` ✅
     - Паркинги - `syncParking()` ✅
     - Дома с участками (поселки) - `syncVillage()` ✅
     - Участки - `syncPlot()` ✅ (нужно создать)
     - Подрядчики - `syncBuilder()` ✅ (если есть API)
     - Коммерция - `syncCommercialBlock()` и `syncCommercialPremise()` ✅

3. **✅ Строгая проверка доступности фото**
   - План: Этап 2.2 - создание `ImageValidationService`
   - Детали: HTTP HEAD/GET запросы, сохранение статуса в БД, логирование недоступных фото

4. **✅ Пункт меню и страницы управления**
   - План: Этап 4 - Административный интерфейс
   - Включает: просмотр, редактирование, удаление, проверку актуальности, создание новых

5. **✅ Диапазон времени для запуска парсера**
   - План: Этап 5 - Планировщик задач
   - Детали: таблица `parser_schedules` с полями `time_from`, `time_to`, `days_of_week`

---

## Предложения для гибкости и расширенных настроек

### 1. Гибкие настройки парсинга

**Таблица `parser_settings` для глобальных настроек:**
```sql
CREATE TABLE parser_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value JSON NULL,
    description TEXT NULL,
    updated_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL
);
```

**Настройки:**
- `default_batch_size` - размер пачки для обработки (по умолчанию 50)
- `max_retries` - количество повторных попыток при ошибке (по умолчанию 3)
- `retry_delay` - задержка между попытками в секундах (по умолчанию 5)
- `image_check_timeout` - таймаут проверки изображений (по умолчанию 5 секунд)
- `api_request_timeout` - таймаут API запросов (по умолчанию 30 секунд)
- `enable_queue` - использовать очереди для фоновой обработки (по умолчанию false)
- `parallel_cities` - количество городов для параллельной обработки (по умолчанию 1)
- `save_full_api_response` - сохранять полный ответ API в metadata (по умолчанию false)

### 2. Фильтры и условия парсинга

**Дополнительные опции команды `trend:parse`:**
```bash
php artisan trend:parse
    --cities=1,2,3                    # ID городов (по умолчанию все активные)
    --types=blocks,parkings           # Типы объектов
    --check-images                    # Проверить фото
    --force                           # Принудительное обновление
    --batch-size=100                  # Размер пачки (переопределяет настройку)
    --offset=0                        # Смещение для продолжения прерванного парсинга
    --limit=1000                      # Максимальное количество объектов на город
    --only-new                        # Только новые объекты (не обновлять существующие)
    --skip-existing                   # Пропустить существующие объекты
    --date-from=2025-01-01            # Парсить объекты измененные после даты
    --date-to=2025-12-31              # Парсить объекты измененные до даты
    --dry-run                         # Тестовый режим (без сохранения в БД)
    --verbose                         # Подробный вывод
    --log-file=parser.log             # Файл для логирования
```

### 3. Настройки для каждого типа объекта

**Расширение таблицы `parser_schedules`:**
```sql
ALTER TABLE parser_schedules ADD COLUMN object_type_settings JSON NULL;
-- Пример: {"batch_size": 50, "check_images": true, "force_update": false, "limit": 1000}
```

**Индивидуальные настройки:**
- Различные настройки для каждого типа объекта
- Разные интервалы парсинга для разных типов
- Приоритеты обработки типов объектов

### 4. Мониторинг и уведомления

**Таблица `parser_notifications`:**
```sql
CREATE TABLE parser_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_type VARCHAR(50) NOT NULL, -- error, warning, info, success
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    context JSON NULL,
    is_read BOOLEAN DEFAULT FALSE,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_user_id (user_id)
);
```

**Типы уведомлений:**
- Критические ошибки парсинга (останавливают процесс)
- Предупреждения (недоступные фото, пропущенные объекты)
- Информация о завершении парсинга
- Изменения цен/статусов объектов
- Истечение токена авторизации

### 5. Статистика и метрики

**Таблица `parser_statistics`:**
```sql
CREATE TABLE parser_statistics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id VARCHAR(100) NOT NULL, -- Уникальный ID запуска
    object_type VARCHAR(50) NOT NULL,
    city_id BIGINT UNSIGNED NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    status VARCHAR(20) NOT NULL, -- running, completed, failed, cancelled
    objects_processed INT DEFAULT 0,
    objects_created INT DEFAULT 0,
    objects_updated INT DEFAULT 0,
    objects_skipped INT DEFAULT 0,
    objects_failed INT DEFAULT 0,
    images_checked INT DEFAULT 0,
    images_unavailable INT DEFAULT 0,
    errors_count INT DEFAULT 0,
    duration_seconds INT NULL,
    metadata JSON NULL,
    INDEX idx_run_id (run_id),
    INDEX idx_object_type (object_type),
    INDEX idx_city_id (city_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
);
```

### 6. Условные правила парсинга

**Таблица `parser_rules`:**
```sql
CREATE TABLE parser_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    object_type VARCHAR(50) NOT NULL,
    conditions JSON NOT NULL, -- {"field": "price", "operator": ">", "value": 10000000}
    actions JSON NOT NULL, -- {"skip": true} или {"set_field": {"status": 0}}
    priority INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Примеры правил:**
- Пропускать объекты с ценой выше определенной
- Автоматически деактивировать объекты с недоступными фото
- Помечать объекты как "требует проверки" при изменении цены более чем на 10%

### 7. Экспорт и импорт данных

**Функциональность:**
- Экспорт результатов парсинга в CSV/Excel
- Импорт объектов из файлов (для ручного добавления)
- Массовый импорт/экспорт настроек парсера
- Резервное копирование данных перед обновлением

### 8. API для внешней интеграции

**REST API endpoints:**
- `GET /api/v1/parser/status` - статус последнего запуска
- `POST /api/v1/parser/trigger` - запуск парсера через API
- `GET /api/v1/parser/statistics` - статистика парсинга
- `GET /api/v1/parser/errors` - список ошибок
- `POST /api/v1/parser/settings` - обновление настроек

### 9. Webhook уведомления

**Таблица `parser_webhooks`:**
```sql
CREATE TABLE parser_webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    events JSON NOT NULL, -- ["parse.completed", "parse.failed", "object.updated"]
    secret VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**События:**
- `parse.started` - начало парсинга
- `parse.completed` - завершение парсинга
- `parse.failed` - ошибка парсинга
- `object.created` - создан новый объект
- `object.updated` - объект обновлен
- `object.price_changed` - изменилась цена
- `image.unavailable` - изображение недоступно

### 10. Гранулярные права доступа

**Роли и разрешения:**
- `parser.view` - просмотр результатов парсинга
- `parser.edit` - редактирование объектов
- `parser.delete` - удаление объектов
- `parser.create` - создание объектов вручную
- `parser.run` - запуск парсера
- `parser.settings` - изменение настроек парсера
- `parser.schedule` - управление расписанием

---

## Обновленный план с дополнительными возможностями

### Фаза 0: Подготовка инфраструктуры настроек (НОВОЕ)
1. Создание таблиц для настроек и статистики
2. Сервис для работы с настройками (`ParserSettingsService`)
3. Модели для статистики и уведомлений

### Фаза 1-7: Без изменений (см. основной план)

### Фаза 8: Расширенные возможности (РАСШИРЕНО)
1. Мониторинг и статистика
2. Уведомления и webhooks
3. Условные правила парсинга
4. Экспорт/импорт данных
5. API для внешней интеграции
6. Гранулярные права доступа

---

*План обновлен с учетом требований задания и предложений для гибкости*

**Версия:** 2.0  
**Дата обновления:** 2025-12-28

