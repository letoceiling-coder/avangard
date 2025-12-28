# Руководство по реализации БД для парсера TrendAgent

Полное руководство по использованию созданной структуры БД.

**Дата создания:** 2025-12-28

---

## 📋 Что было создано

### ✅ Миграции (17 файлов)

Все миграции находятся в `database/migrations/` и создают полную структуру БД:

1. **Справочники:**
   - cities, regions, locations
   - builders
   - subway_lines, subways

2. **Основные таблицы:**
   - blocks (ЖК)
   - parkings (Паркинг)
   - villages (Поселки)
   - commercial_blocks (Коммерция)

3. **Связующие таблицы:**
   - block_subways, parking_subways, commercial_block_subways
   - block_prices, village_prices

4. **Вспомогательные:**
   - images (полиморфная)
   - data_sources (логи источников)

### ✅ Модели (3 файла)

- `app/Models/Trend/BaseTrendModel.php` - Базовая модель
- `app/Models/Image.php` - Модель изображений
- `app/Models/DataSource.php` - Модель источников данных

### ✅ Документация (3 файла)

- `TREND_DATABASE_DESIGN.md` - Основное описание структуры
- `TREND_DATABASE_COMPLETE.md` - Полное описание с примерами
- `TREND_DB_MIGRATIONS_SUMMARY.md` - Сводка миграций

---

## 🚀 Запуск миграций

```bash
# Запустить все миграции
php artisan migrate

# Проверить статус
php artisan migrate:status

# Откатить последнюю миграцию (если нужно)
php artisan migrate:rollback
```

---

## 📝 Что нужно создать дальше

### 1. Модели Eloquent

Создать следующие модели в `app/Models/Trend/`:

```bash
# Справочники
app/Models/Trend/City.php
app/Models/Trend/Region.php
app/Models/Trend/Location.php
app/Models/Trend/Builder.php
app/Models/Trend/SubwayLine.php
app/Models/Trend/Subway.php

# Основные объекты
app/Models/Trend/Block.php
app/Models/Trend/BlockPrice.php
app/Models/Trend/Parking.php
app/Models/Trend/Village.php
app/Models/Trend/VillagePrice.php
app/Models/Trend/CommercialBlock.php
```

**Все модели должны:**
- Наследоваться от `BaseTrendModel` (для объектов) или `Model` (для справочников)
- Иметь правильные отношения (`belongsTo`, `hasMany`, `belongsToMany`)
- Использовать trait `Filterable` (для объектов с фильтрацией)
- Иметь правильные `$fillable` и `$casts`

### 2. Фильтры

Создать в `app/Http/Filters/`:

```php
app/Http/Filters/BlockFilter.php
app/Http/Filters/ParkingFilter.php
app/Http/Filters/VillageFilter.php
app/Http/Filters/CommercialBlockFilter.php
```

**Пример структуры:**
- Наследоваться от `AbstractFilter`
- Реализовать `getCallbacks()` с методами фильтрации
- Использовать `before()` для фильтров по умолчанию

### 3. Ресурсы (API Resources)

Создать в `app/Http/Resources/`:

```php
app/Http/Resources/CityResource.php
app/Http/Resources/RegionResource.php
app/Http/Resources/BuilderResource.php
app/Http/Resources/SubwayResource.php
app/Http/Resources/BlockResource.php
app/Http/Resources/BlockPriceResource.php
app/Http/Resources/ParkingResource.php
app/Http/Resources/VillageResource.php
app/Http/Resources/CommercialBlockResource.php
app/Http/Resources/ImageResource.php
```

### 4. Form Requests

Создать в `app/Http/Requests/`:

```php
# Для каждого типа объекта
app/Http/Requests/StoreBlockRequest.php
app/Http/Requests/UpdateBlockRequest.php
app/Http/Requests/StoreParkingRequest.php
app/Http/Requests/UpdateParkingRequest.php
# и т.д.
```

### 5. Контроллеры

Создать в `app/Http/Controllers/Api/`:

```php
app/Http/Controllers/Api/BlockController.php
app/Http/Controllers/Api/ParkingController.php
app/Http/Controllers/Api/VillageController.php
app/Http/Controllers/Api/CommercialBlockController.php
```

---

## 🔧 Примеры использования

### Создание записи из парсера

```php
use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;

// 1. Найти или создать справочники
$city = City::firstOrCreate(
    ['guid' => 'msk'],
    ['name' => 'Москва', 'is_active' => true]
);

$builder = Builder::firstOrCreate(
    ['guid' => 'Capitalgroup'],
    ['name' => 'Capital Group', 'is_active' => true]
);

// 2. Найти или создать блок
$block = Block::updateOrCreate(
    [
        'external_id' => '5ab8d3187be62f4b7f09eb9e', // Или по guid + city_id
    ],
    [
        'city_id' => $city->id,
        'builder_id' => $builder->id,
        'guid' => 'oko',
        'name' => 'МФК ОКО',
        'address' => '1-й Красногвардейский проезд',
        'latitude' => 55.749885579644584,
        'longitude' => 37.5343220970532,
        'min_price' => 5000000, // В копейках!
        'data_source' => 'parser',
        'is_active' => true,
    ]
);

// 3. Пометить как спарсенное
$block->markAsParsed();

// 4. Логировать источник
$block->dataSources()->create([
    'source_type' => 'parser',
    'source_name' => 'TrendAgent API',
    'processed_at' => now(),
]);
```

### Создание записи вручную через админку

```php
use App\Models\Trend\Block;
use Illuminate\Support\Facades\Auth;

$block = Block::create([
    'city_id' => $request->city_id,
    'builder_id' => $request->builder_id,
    'guid' => Str::slug($request->name),
    'name' => $request->name,
    'data_source' => 'manual',
    'is_active' => true,
]);

// Логировать источник
$block->dataSources()->create([
    'source_type' => 'manual',
    'source_name' => 'Admin Panel',
    'user_id' => Auth::id(),
    'processed_at' => now(),
]);
```

### Запрос с фильтрами

```php
use App\Http\Filters\BlockFilter;
use App\Models\Trend\Block;
use App\Http\Resources\BlockResource;

$blocks = Block::query()
    ->with(['city', 'builder', 'mainImage'])
    ->filter(new BlockFilter([
        'city_id' => 1,
        'is_exclusive' => true,
        'min_price' => 5000000,
        'max_price' => 15000000,
        'search' => 'ОКО',
        'sort' => 'price',
        'sort_direction' => 'asc',
    ]))
    ->paginate(20);

return BlockResource::collection($blocks);
```

### Работа с изображениями

```php
// Создание изображения
$block->images()->create([
    'external_id' => '63c00c0b9a85d5af16f5804c',
    'path' => 'w0/wu/',
    'file_name' => '250ea8a64f4cadf7c24dd727674c0e4a.png',
    'url_thumbnail' => 'https://selcdn.trendagent.ru/images/w0/wu/m_250ea8a64f4cadf7c24dd727674c0e4a.png',
    'url_full' => 'https://selcdn.trendagent.ru/images/w0/wu/250ea8a64f4cadf7c24dd727674c0e4a.png',
    'is_main' => true,
    'sort_order' => 0,
]);

// Получение изображений
$mainImage = $block->mainImage;
$allImages = $block->images;
```

### Синхронизация метро

```php
use App\Models\Trend\Subway;

$subway1 = Subway::where('guid', 'mezhdunarodnaya')->first();
$subway2 = Subway::where('guid', 'delovoy-centr')->first();

$block->subways()->sync([
    $subway1->id => [
        'distance_time' => 5,
        'distance_type_id' => 1,
        'distance_type' => 'пешком',
        'priority' => 500,
    ],
    $subway2->id => [
        'distance_time' => 10,
        'distance_type_id' => 1,
        'distance_type' => 'пешком',
        'priority' => 400,
    ],
]);
```

### Обновление данных

```php
// Найти по external_id
$block = Block::where('external_id', '5ab8d3187be62f4b7f09eb9e')->first();

if ($block) {
    // Обновить
    $block->update([
        'apartments_count' => 60,
        'min_price' => 6000000,
        'last_synced_at' => now(),
    ]);
    
    $block->markAsSynced();
}
```

### Удаление неактуальных записей

```php
// Вариант 1: Soft delete
$oldBlocks = Block::where('last_synced_at', '<', now()->subDays(30))
    ->where('data_source', 'parser')
    ->get();

foreach ($oldBlocks as $block) {
    $block->delete(); // Soft delete
}

// Вариант 2: Деактивация
Block::where('last_synced_at', '<', now()->subDays(30))
    ->where('data_source', 'parser')
    ->update(['is_active' => false]);
```

---

## 📊 Структура API

### Endpoints (пример для блоков)

```
GET    /api/v1/blocks              - Список с фильтрацией
POST   /api/v1/blocks              - Создание
GET    /api/v1/blocks/{id}         - Просмотр
PUT    /api/v1/blocks/{id}         - Обновление
DELETE /api/v1/blocks/{id}         - Удаление
```

### Параметры фильтрации (GET /api/v1/blocks)

```
?city_id=1
&region_id=5
&builder_id=10
&is_exclusive=true
&min_price=5000000
&max_price=15000000
&search=ОКО
&subway_id=3
&sort=price
&sort_direction=asc
&page=1
&per_page=20
```

---

## 🔍 Важные моменты

### 1. Цены

**ВСЕГДА хранить в копейках (integer):**

```php
// ✅ Правильно
'min_price' => 5000000, // 50,000,000 копеек = 500,000 рублей

// ❌ Неправильно
'min_price' => 500000.00, // Плавающая точка - потеря точности
```

**Форматирование при выводе:**

```php
// В модели (accessor)
public function getFormattedMinPriceAttribute(): ?string
{
    return $this->min_price ? number_format($this->min_price / 100, 0, '.', ' ') . ' ₽' : null;
}

// В API (resource)
'min_price' => $this->min_price, // В копейках
'min_price_formatted' => $this->formatted_min_price, // Форматированная строка
```

### 2. Координаты

**Использовать decimal с точностью:**

```php
// В миграции
$table->decimal('latitude', 10, 8);   // До 8 знаков после запятой
$table->decimal('longitude', 11, 8);

// В модели
protected $casts = [
    'latitude' => 'decimal:8',
    'longitude' => 'decimal:8',
];
```

### 3. Источники данных

**Всегда логировать источник:**

```php
// При создании/обновлении
$block->dataSources()->create([
    'source_type' => 'parser', // или 'manual', 'feed', 'import'
    'source_name' => 'TrendAgent API',
    'user_id' => Auth::id(), // если есть пользователь
    'processed_at' => now(),
]);
```

### 4. Soft Deletes

**Использовать для восстановления:**

```php
// Удаление
$block->delete(); // Устанавливает deleted_at

// Восстановление
$block->restore(); // Очищает deleted_at

// Полное удаление (если нужно)
$block->forceDelete();
```

### 5. Индексы

**Все важные поля проиндексированы:**
- Внешние ключи (автоматически)
- Часто используемые поля (city_id, builder_id, is_active)
- Поисковые поля (guid, external_id)
- Географические поля (latitude, longitude)
- Полнотекстовый поиск (name, address)

---

## ✅ Чеклист перед использованием

- [ ] Запустить миграции: `php artisan migrate`
- [ ] Создать все модели Eloquent
- [ ] Создать фильтры для каждого типа объекта
- [ ] Создать ресурсы для API
- [ ] Создать FormRequest классы
- [ ] Создать контроллеры
- [ ] Добавить роуты в `routes/api.php`
- [ ] Протестировать создание записи из парсера
- [ ] Протестировать создание записи вручную
- [ ] Протестировать фильтрацию
- [ ] Протестировать API endpoints

---

**Полная документация в TREND_DATABASE_DESIGN.md и TREND_DATABASE_COMPLETE.md**

