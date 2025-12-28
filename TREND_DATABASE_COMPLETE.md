# Полное описание базы данных для парсера TrendAgent

Полная документация со всеми моделями, фильтрами, ресурсами, запросами и примерами использования.

**Дата создания:** 2025-12-28

---

## 📋 Оглавление

1. [Структура таблиц](#структура-таблиц)
2. [Модели Eloquent](#модели-eloquent)
3. [Фильтры](#фильтры)
4. [Ресурсы (API Resources)](#ресурсы-api-resources)
5. [Form Requests](#form-requests)
6. [Контроллеры](#контроллеры)
7. [Примеры использования](#примеры-использования)

---

## 📊 Структура таблиц

### Справочники

- `cities` - Города
- `regions` - Районы
- `locations` - Локации/Округа
- `builders` - Застройщики
- `subway_lines` - Линии метро
- `subways` - Станции метро

### Основные таблицы объектов

- `blocks` - ЖК / Блоки квартир
- `block_subways` - Связь блоков и станций метро (pivot)
- `block_prices` - Цены по типам квартир

- `parkings` - Парковочные места
- `parking_subways` - Связь паркинга и метро (pivot)

- `villages` - Поселки / Дома с участками
- `village_prices` - Цены поселков по типам участков

- `commercial_blocks` - Коммерческие блоки
- `commercial_block_subways` - Связь коммерции и метро (pivot)

### Вспомогательные таблицы

- `images` - Полиморфная таблица изображений
- `data_sources` - Логи источников данных

---

## 🎯 Модели Eloquent

### Базовые модели

#### BaseTrendModel

См. `app/Models/Trend/BaseTrendModel.php`

**Особенности:**
- Soft Deletes
- Полиморфные связи с Image и DataSource
- Scopes для фильтрации по источнику данных

#### Image

См. `app/Models/Image.php`

**Методы:**
- `full_url` - Accessor для полного URL
- `thumbnail_url` - Accessor для миниатюры
- `imageable()` - Полиморфная связь

#### DataSource

См. `app/Models/DataSource.php`

**Назначение:**
- Логирование источников данных
- Отслеживание истории изменений

---

### Справочники

#### City

```php
namespace App\Models\Trend;

class City extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['guid', 'name', 'crm_id', 'external_id', 'is_active', 'sort_order'];
    
    protected $casts = ['is_active' => 'boolean'];
    
    public function regions()
    {
        return $this->hasMany(Region::class);
    }
    
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    
    public function subways()
    {
        return $this->hasMany(Subway::class);
    }
    
    public function blocks()
    {
        return $this->hasMany(Block::class);
    }
}
```

#### Builder

```php
namespace App\Models\Trend;

class Builder extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'guid', 'name', 'crm_id', 'external_id',
        'description', 'website', 'email', 'phone',
        'is_active', 'is_exclusive', 'sort_order'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_exclusive' => 'boolean',
    ];
    
    public function blocks()
    {
        return $this->hasMany(Block::class);
    }
    
    public function parkings()
    {
        return $this->hasMany(Parking::class);
    }
    
    public function villages()
    {
        return $this->hasMany(Village::class);
    }
    
    public function commercialBlocks()
    {
        return $this->hasMany(CommercialBlock::class);
    }
}
```

#### Subway

```php
namespace App\Models\Trend;

class Subway extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'subway_line_id', 'city_id', 'guid', 'name',
        'crm_id', 'external_id',
        'latitude', 'longitude', 'priority',
        'is_active', 'sort_order'
    ];
    
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];
    
    public function subwayLine()
    {
        return $this->belongsTo(SubwayLine::class);
    }
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function blocks()
    {
        return $this->belongsToMany(Block::class, 'block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'distance_type', 'priority'])
            ->withTimestamps();
    }
    
    public function parkings()
    {
        return $this->belongsToMany(Parking::class, 'parking_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps();
    }
}
```

---

### Основные модели объектов

#### Block (ЖК)

```php
namespace App\Models\Trend;

use App\Models\Traits\Filterable;

class Block extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'city_id', 'region_id', 'location_id', 'builder_id',
        'guid', 'name', 'address', 'crm_id', 'external_id',
        'latitude', 'longitude',
        'status', 'edit_mode', 'is_suite', 'is_exclusive', 'is_marked', 'is_active',
        'min_price', 'max_price',
        'apartments_count', 'view_apartments_count', 'exclusive_apartments_count',
        'deadline', 'deadline_date', 'deadline_over_check', 'finishing',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata', 'advantages', 'payment_types', 'contract_types', 'installments',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'advantages' => 'array',
        'payment_types' => 'array',
        'contract_types' => 'array',
        'installments' => 'array',
        'is_suite' => 'boolean',
        'is_exclusive' => 'boolean',
        'is_marked' => 'boolean',
        'is_active' => 'boolean',
        'deadline_over_check' => 'boolean',
        'deadline_date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'min_price' => 'integer',
        'max_price' => 'integer',
    ];
    
    // Отношения
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    
    public function builder()
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function subways()
    {
        return $this->belongsToMany(Subway::class, 'block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'distance_type', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
    
    public function prices()
    {
        return $this->hasMany(BlockPrice::class)->orderBy('sort_order');
    }
    
    // Scopes
    public function scopeExclusive($query)
    {
        return $query->where('is_exclusive', true);
    }
    
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
    
    public function scopeByBuilder($query, $builderId)
    {
        return $query->where('builder_id', $builderId);
    }
    
    // Accessors
    public function getFormattedMinPriceAttribute(): ?string
    {
        return $this->min_price ? number_format($this->min_price / 100, 0, '.', ' ') . ' ₽' : null;
    }
    
    public function getFormattedMaxPriceAttribute(): ?string
    {
        return $this->max_price ? number_format($this->max_price / 100, 0, '.', ' ') . ' ₽' : null;
    }
}
```

#### BlockPrice

```php
namespace App\Models\Trend;

class BlockPrice extends Model
{
    protected $fillable = [
        'block_id', 'room_type_id', 'room_type_name', 'price', 'sort_order'
    ];
    
    protected $casts = [
        'price' => 'integer',
        'room_type_id' => 'integer',
        'sort_order' => 'integer',
    ];
    
    public function block()
    {
        return $this->belongsTo(Block::class);
    }
    
    public function getFormattedPriceAttribute(): ?string
    {
        return $this->price ? number_format($this->price / 100, 0, '.', ' ') . ' ₽' : null;
    }
}
```

#### Parking

```php
namespace App\Models\Trend;

use App\Models\Traits\Filterable;

class Parking extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'block_id', 'city_id', 'district_id', 'location_id', 'builder_id',
        'external_id', 'block_guid', 'block_name', 'number', 'floor', 'area',
        'latitude', 'longitude',
        'parking_type', 'place_type', 'property_type', 'status', 'status_label',
        'price', 'reward_label',
        'deadline', 'deadline_date', 'deadline_over_check',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'floor' => 'integer',
        'area' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price' => 'integer',
        'deadline_over_check' => 'boolean',
        'deadline_date' => 'datetime',
    ];
    
    public function block()
    {
        return $this->belongsTo(Block::class);
    }
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function district()
    {
        return $this->belongsTo(Region::class, 'district_id');
    }
    
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    
    public function builder()
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function subways()
    {
        return $this->belongsToMany(Subway::class, 'parking_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
}
```

#### Village (Поселок)

```php
namespace App\Models\Trend;

use App\Models\Traits\Filterable;

class Village extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'city_id', 'builder_id',
        'guid', 'name', 'address', 'external_id',
        'plots_count', 'view_plots_count',
        'distance',
        'deadline', 'deadline_date', 'sales_start', 'sales_start_date',
        'reward_label',
        'is_new_village', 'is_active',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata', 'property_types',
    ];
    
    protected $casts = [
        'distance' => 'array',
        'metadata' => 'array',
        'property_types' => 'array',
        'is_new_village' => 'boolean',
        'is_active' => 'boolean',
        'deadline_date' => 'datetime',
        'sales_start_date' => 'datetime',
    ];
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function builder()
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function prices()
    {
        return $this->hasMany(VillagePrice::class)->orderBy('sort_order');
    }
}
```

#### CommercialBlock

```php
namespace App\Models\Trend;

use App\Models\Traits\Filterable;

class CommercialBlock extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'city_id', 'builder_id', 'district_id', 'location_id',
        'guid', 'name', 'address', 'external_id',
        'premises_count', 'booked_premises_count',
        'is_new_block', 'is_active',
        'deadlines', 'deadline_date', 'deadline_over_check', 'sales_start_at',
        'reward_label',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata', 'property_types', 'min_prices',
    ];
    
    protected $casts = [
        'deadlines' => 'array',
        'sales_start_at' => 'array',
        'metadata' => 'array',
        'property_types' => 'array',
        'min_prices' => 'array',
        'is_new_block' => 'boolean',
        'is_active' => 'boolean',
        'deadline_over_check' => 'boolean',
        'deadline_date' => 'datetime',
    ];
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function builder()
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function district()
    {
        return $this->belongsTo(Region::class, 'district_id');
    }
    
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    
    public function subways()
    {
        return $this->belongsToMany(Subway::class, 'commercial_block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
}
```

---

## 🔍 Фильтры

Все фильтры наследуются от `AbstractFilter` и используют trait `Filterable` в моделях.

### BlockFilter

См. полный код в `TREND_DATABASE_DESIGN.md`

**Поддерживаемые фильтры:**
- `city_id`, `region_id`, `location_id`, `builder_id`
- `is_exclusive`, `is_active`
- `min_price`, `max_price`
- `deadline`, `finishing`
- `data_source`
- `search` (полнотекстовый поиск)
- `subway_id`
- `sort` + `sort_direction`

### ParkingFilter

Аналогично BlockFilter, но со специфичными полями паркинга.

### VillageFilter

Аналогично, но для поселков.

### CommercialBlockFilter

Аналогично, но для коммерции.

---

## 📦 Ресурсы (API Resources)

### BlockResource

См. полный код в `TREND_DATABASE_DESIGN.md`

**Особенности:**
- Условная загрузка связей через `whenLoaded()`
- Форматированные цены
- Вложенные ресурсы для связанных моделей

### ImageResource

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'file_name' => $this->file_name,
            'path' => $this->path,
            'url_thumbnail' => $this->thumbnail_url,
            'url_full' => $this->full_url,
            'alt' => $this->alt,
            'title' => $this->title,
            'description' => $this->description,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->size,
            'is_main' => $this->is_main,
            'sort_order' => $this->sort_order,
        ];
    }
}
```

---

## ✅ Form Requests

### StoreBlockRequest / UpdateBlockRequest

См. полный код в `TREND_DATABASE_DESIGN.md`

**Особенности:**
- Валидация всех полей
- Автоматическое заполнение `data_source` и `is_active` если не указаны
- Валидация связей (city_id, builder_id и т.д.)

---

## 🎮 Контроллеры

### BlockController

См. пример в `TREND_DATABASE_DESIGN.md`

**Методы:**
- `index()` - Список с фильтрацией и пагинацией
- `store()` - Создание с логированием источника
- `show()` - Просмотр одного объекта
- `update()` - Обновление
- `destroy()` - Мягкое удаление

---

## 📖 Примеры использования

### Создание блока из парсера

```php
use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;

// Найти или создать город
$city = City::firstOrCreate(
    ['guid' => 'msk'],
    ['name' => 'Москва', 'is_active' => true]
);

// Найти или создать застройщика
$builder = Builder::firstOrCreate(
    ['guid' => 'Capitalgroup'],
    ['name' => 'Capital Group', 'is_active' => true]
);

// Создать блок
$block = Block::create([
    'city_id' => $city->id,
    'builder_id' => $builder->id,
    'guid' => 'oko',
    'name' => 'МФК ОКО',
    'address' => '1-й Красногвардейский проезд',
    'external_id' => '5ab8d3187be62f4b7f09eb9e',
    'latitude' => 55.749885579644584,
    'longitude' => 37.5343220970532,
    'min_price' => 5000000, // В копейках
    'data_source' => 'parser',
    'is_active' => true,
]);

// Пометить как спарсенное
$block->markAsParsed();

// Логировать источник
$block->dataSources()->create([
    'source_type' => 'parser',
    'source_name' => 'TrendAgent API',
    'processed_at' => now(),
]);
```

### Запрос с фильтрами

```php
use App\Http\Filters\BlockFilter;
use App\Models\Trend\Block;

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
```

### Обновление существующего блока

```php
$block = Block::where('external_id', '5ab8d3187be62f4b7f09eb9e')->first();

if ($block) {
    // Обновить данные
    $block->update([
        'apartments_count' => 60,
        'min_price' => 6000000,
        'last_synced_at' => now(),
    ]);
    
    $block->markAsSynced();
} else {
    // Создать новый
    $block = Block::create([...]);
}
```

### Работа с изображениями

```php
// Создать изображение для блока
$block->images()->create([
    'external_id' => '63c00c0b9a85d5af16f5804c',
    'path' => 'w0/wu/',
    'file_name' => '250ea8a64f4cadf7c24dd727674c0e4a.png',
    'is_main' => true,
    'sort_order' => 0,
]);

// Получить главное изображение
$mainImage = $block->mainImage;
echo $mainImage->thumbnail_url; // URL миниатюры
echo $mainImage->full_url; // URL полного изображения
```

### Синхронизация метро

```php
// Найти станции метро
$subway1 = Subway::where('guid', 'mezhdunarodnaya')->first();
$subway2 = Subway::where('guid', 'delovoy-centr')->first();

// Привязать к блоку
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

### Получение данных для API

```php
use App\Http\Resources\BlockResource;

$block = Block::with([
    'city',
    'region',
    'location',
    'builder',
    'subways',
    'prices',
    'images',
    'mainImage'
])->find(1);

return new BlockResource($block);
```

---

## 🔄 Обновление данных из парсера

### Стратегия обновления

1. **Поиск существующей записи:**
   - По `external_id` (если есть)
   - По `guid` + `city_id` (если нет external_id)

2. **Если найдено:**
   - Обновить поля
   - Обновить `last_synced_at`
   - Создать запись в `data_sources`

3. **Если не найдено:**
   - Создать новую запись
   - Установить `data_source = 'parser'`
   - Установить `parsed_at = now()`

4. **Для удаленных из API:**
   - Вариант 1: Soft delete через `deleted_at`
   - Вариант 2: Установить `is_active = false`

---

**Документ содержит полное описание структуры БД для парсера TrendAgent**

