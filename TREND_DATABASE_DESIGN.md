# Проектирование базы данных для парсера TrendAgent

Полное описание структуры БД с миграциями, моделями, фильтрами, ресурсами и запросами.

**Дата создания:** 2025-12-28  
**Цель:** Гибкая структура БД для хранения данных из парсера, админки и файлов/feed

---

## 📋 Содержание

1. [Архитектура БД](#архитектура-бд)
2. [Миграции](#миграции)
3. [Модели](#модели)
4. [Фильтры](#фильтры)
5. [Ресурсы](#ресурсы)
6. [Запросы](#запросы)
7. [Отношения](#отношения)

---

## 🗄️ Архитектура БД

### Диаграмма основных таблиц

```
┌─────────────┐
│   cities    │
└──────┬──────┘
       │
       ├───┐
       │   │
┌──────▼───▼──────┐
│   regions       │
└──────┬──────────┘
       │
       ├───┐
       │   │
┌──────▼───▼──────┐     ┌──────────────┐
│   locations     │     │   builders   │
└──────┬──────────┘     └──────┬───────┘
       │                       │
       │              ┌────────┴────────┐
       │              │                 │
┌──────▼──────────────▼─────────────────▼──────────┐
│              blocks (apartments)                 │
└──────┬───────────────────────────────────────────┘
       │
       ├───┐
       │   │
┌──────▼───▼──────────┐
│  block_subways      │ (pivot)
└──────┬──────────────┘
       │
┌──────▼──────┐
│   subways   │
└─────────────┘
```

### Типы источников данных

```php
enum DataSource: string
{
    case PARSER = 'parser';      // Из парсера TrendAgent API
    case MANUAL = 'manual';      // Создано вручную через админку
    case FEED = 'feed';          // Загружено через файл/feed
    case IMPORT = 'import';      // Импортировано из другого источника
}
```

---

## 📊 Миграции

### 1. Справочники (Directories)

#### cities (Города)

```php
Schema::create('cities', function (Blueprint $table) {
    $table->id();
    $table->string('guid')->unique();           // Уникальный slug из API
    $table->string('name');                     // Название города
    $table->unsignedBigInteger('crm_id')->nullable()->unique(); // ID в CRM
    $table->string('external_id')->nullable()->index(); // ID из внешней системы (MongoDB _id)
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['guid', 'is_active']);
});
```

#### regions (Районы)

```php
Schema::create('regions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('city_id')->constrained()->onDelete('cascade');
    $table->string('guid')->unique();
    $table->string('name');
    $table->unsignedBigInteger('crm_id')->nullable();
    $table->string('external_id')->nullable()->index();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['city_id', 'is_active']);
    $table->index(['guid', 'city_id']);
});
```

#### locations (Локации/Округа)

```php
Schema::create('locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('city_id')->constrained()->onDelete('cascade');
    $table->string('guid')->unique();
    $table->string('name');
    $table->unsignedBigInteger('crm_id')->nullable();
    $table->string('external_id')->nullable()->index();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['city_id', 'is_active']);
});
```

#### builders (Застройщики)

```php
Schema::create('builders', function (Blueprint $table) {
    $table->id();
    $table->string('guid')->unique();
    $table->string('name');
    $table->unsignedBigInteger('crm_id')->nullable();
    $table->string('external_id')->nullable()->index();
    $table->text('description')->nullable();
    $table->string('website')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_exclusive')->default(false);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['guid', 'is_active']);
    $table->index('is_exclusive');
});
```

#### subway_lines (Линии метро)

```php
Schema::create('subway_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('city_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('color', 7)->nullable();     // Hex цвет (#2489c2)
    $table->integer('line_number')->nullable();
    $table->string('external_id')->nullable()->index();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['city_id', 'line_number']);
});
```

#### subways (Станции метро)

```php
Schema::create('subways', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subway_line_id')->constrained()->onDelete('cascade');
    $table->foreignId('city_id')->constrained()->onDelete('cascade');
    $table->string('guid')->unique();
    $table->string('name');
    $table->unsignedBigInteger('crm_id')->nullable();
    $table->string('external_id')->nullable()->index();
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    $table->integer('priority')->default(500);  // Приоритет отображения
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['subway_line_id', 'city_id']);
    $table->index(['latitude', 'longitude']);
    $table->index(['guid', 'city_id']);
});
```

---

### 2. Основные таблицы объектов

#### blocks (ЖК / Блоки квартир)

```php
Schema::create('blocks', function (Blueprint $table) {
    $table->id();
    
    // Связи со справочниками
    $table->foreignId('city_id')->constrained()->onDelete('restrict');
    $table->foreignId('region_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
    
    // Основные данные
    $table->string('guid')->unique();
    $table->string('name');
    $table->text('address')->nullable();        // JSON или текст
    $table->unsignedBigInteger('crm_id')->nullable();
    $table->string('external_id')->nullable()->index(); // MongoDB _id
    
    // Координаты
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    
    // Статусы и флаги
    $table->integer('status')->default(1);      // 1 = активен
    $table->integer('edit_mode')->nullable();
    $table->boolean('is_suite')->default(false);
    $table->boolean('is_exclusive')->default(false);
    $table->boolean('is_marked')->default(false);
    $table->boolean('is_active')->default(true);
    
    // Цены
    $table->unsignedBigInteger('min_price')->nullable();  // В копейках
    $table->unsignedBigInteger('max_price')->nullable();
    
    // Статистика
    $table->unsignedInteger('apartments_count')->default(0);
    $table->unsignedInteger('view_apartments_count')->default(0);
    $table->unsignedInteger('exclusive_apartments_count')->default(0);
    
    // Сроки и отделка
    $table->string('deadline')->nullable();     // Текст срока сдачи
    $table->timestamp('deadline_date')->nullable(); // Дата сдачи
    $table->boolean('deadline_over_check')->default(false);
    $table->string('finishing')->nullable();    // Тип отделки
    
    // Источник данных
    $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
    $table->timestamp('parsed_at')->nullable(); // Когда был спарсен
    $table->timestamp('last_synced_at')->nullable(); // Последняя синхронизация
    
    // Метаданные
    $table->json('metadata')->nullable();       // Дополнительные данные
    $table->json('advantages')->nullable();     // Преимущества (массив)
    $table->json('payment_types')->nullable();  // Типы оплаты
    $table->json('contract_types')->nullable(); // Типы договоров
    $table->json('installments')->nullable();   // Рассрочка
    
    $table->timestamps();
    $table->softDeletes();
    
    // Индексы
    $table->index(['city_id', 'is_active', 'status']);
    $table->index(['builder_id', 'is_active']);
    $table->index(['guid', 'city_id']);
    $table->index(['data_source', 'parsed_at']);
    $table->index(['latitude', 'longitude']);
    $table->index('is_exclusive');
    $table->fullText(['name', 'address']);      // Полнотекстовый поиск
});
```

#### block_subways (Связь блоков и станций метро)

```php
Schema::create('block_subways', function (Blueprint $table) {
    $table->id();
    $table->foreignId('block_id')->constrained()->onDelete('cascade');
    $table->foreignId('subway_id')->constrained()->onDelete('cascade');
    $table->integer('distance_time')->nullable();      // Время в минутах
    $table->integer('distance_type_id')->nullable();   // 1 = пешком, 2 = транспортом
    $table->string('distance_type')->nullable();       // "пешком", "транспортом"
    $table->integer('priority')->default(500);
    $table->timestamps();
    
    $table->unique(['block_id', 'subway_id']);
    $table->index(['block_id', 'priority']);
});
```

#### block_prices (Цены по типам квартир)

```php
Schema::create('block_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('block_id')->constrained()->onDelete('cascade');
    $table->integer('room_type_id')->nullable();       // Код типа (60 = свободная планировка)
    $table->string('room_type_name')->nullable();      // Название ("Студия", "1-к")
    $table->unsignedBigInteger('price')->nullable();   // В копейках
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->index(['block_id', 'room_type_id']);
});
```

#### parkings (Паркинг)

```php
Schema::create('parkings', function (Blueprint $table) {
    $table->id();
    
    // Связи
    $table->foreignId('block_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('city_id')->constrained()->onDelete('restrict');
    $table->foreignId('district_id')->nullable()->constrained('regions')->onDelete('set null');
    $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
    
    // Основные данные
    $table->string('external_id')->nullable()->index();
    $table->string('block_guid')->nullable();          // GUID блока из API
    $table->string('block_name')->nullable();
    $table->string('number')->nullable();              // Номер места
    $table->integer('floor')->nullable();              // Этаж (может быть отрицательным)
    $table->decimal('area', 8, 2)->nullable();         // Площадь в м²
    
    // Координаты
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    
    // Типы и статусы
    $table->string('parking_type')->nullable();        // "Подземный", "Наземный"
    $table->string('place_type')->nullable();          // "Увеличенное", "Стандартное"
    $table->string('property_type')->nullable();       // "new", "secondary"
    $table->string('status')->default('available');    // "available", "booked"
    $table->string('status_label')->nullable();        // "Свободноe"
    
    // Цена и комиссия
    $table->unsignedBigInteger('price')->nullable();
    $table->string('reward_label')->nullable();        // "0.6-0.8%"
    
    // Сроки
    $table->string('deadline')->nullable();
    $table->timestamp('deadline_date')->nullable();
    $table->boolean('deadline_over_check')->default(false);
    
    // Источник данных
    $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
    $table->timestamp('parsed_at')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    
    // Метаданные
    $table->json('metadata')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['block_id', 'status']);
    $table->index(['city_id', 'status']);
    $table->index(['data_source', 'parsed_at']);
    $table->index(['latitude', 'longitude']);
});
```

#### parking_subways (Связь паркинга и метро)

```php
Schema::create('parking_subways', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parking_id')->constrained()->onDelete('cascade');
    $table->foreignId('subway_id')->constrained()->onDelete('cascade');
    $table->integer('distance_time')->nullable();
    $table->integer('distance_type_id')->nullable();
    $table->integer('priority')->default(500);
    $table->timestamps();
    
    $table->unique(['parking_id', 'subway_id']);
});
```

#### villages (Поселки / Дома с участками)

```php
Schema::create('villages', function (Blueprint $table) {
    $table->id();
    
    // Связи
    $table->foreignId('city_id')->constrained()->onDelete('restrict');
    $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
    
    // Основные данные
    $table->string('guid')->unique();
    $table->string('name');
    $table->text('address')->nullable();
    $table->string('external_id')->nullable()->index();
    
    // Статистика
    $table->unsignedInteger('plots_count')->default(0);
    $table->unsignedInteger('view_plots_count')->default(0);
    
    // Расстояния
    $table->json('distance')->nullable();       // До центра, ж/д, трассы
    
    // Сроки и старт продаж
    $table->string('deadline')->nullable();
    $table->timestamp('deadline_date')->nullable();
    $table->string('sales_start')->nullable();
    $table->timestamp('sales_start_date')->nullable();
    
    // Комиссия
    $table->string('reward_label')->nullable();
    
    // Флаги
    $table->boolean('is_new_village')->default(false);
    $table->boolean('is_active')->default(true);
    
    // Источник данных
    $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
    $table->timestamp('parsed_at')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    
    // Метаданные
    $table->json('metadata')->nullable();
    $table->json('property_types')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['city_id', 'is_active']);
    $table->index(['builder_id', 'is_active']);
    $table->index(['data_source', 'parsed_at']);
    $table->fullText(['name', 'address']);
});
```

#### village_prices (Цены поселков по типам участков)

```php
Schema::create('village_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('village_id')->constrained()->onDelete('cascade');
    $table->string('label')->nullable();               // "Участки 5-10 сот."
    $table->unsignedBigInteger('price')->nullable();   // В копейках
    $table->unsignedBigInteger('unformatted_price')->nullable();
    $table->string('unit')->default('₽');
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->index(['village_id', 'sort_order']);
});
```

#### commercial_blocks (Коммерческие блоки)

```php
Schema::create('commercial_blocks', function (Blueprint $table) {
    $table->id();
    
    // Связи
    $table->foreignId('city_id')->constrained()->onDelete('restrict');
    $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('district_id')->nullable()->constrained('regions')->onDelete('set null');
    $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
    
    // Основные данные
    $table->string('guid')->unique();
    $table->string('name');
    $table->text('address')->nullable();
    $table->string('external_id')->nullable()->index();
    
    // Статистика
    $table->unsignedInteger('premises_count')->default(0);
    $table->unsignedInteger('booked_premises_count')->default(0);
    
    // Флаги
    $table->boolean('is_new_block')->default(false);
    $table->boolean('is_active')->default(true);
    
    // Сроки
    $table->json('deadlines')->nullable();             // Массив сроков
    $table->timestamp('deadline_date')->nullable();
    $table->boolean('deadline_over_check')->default(false);
    $table->json('sales_start_at')->nullable();       // Массив дат старта продаж
    
    // Комиссия
    $table->string('reward_label')->nullable();
    
    // Источник данных
    $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
    $table->timestamp('parsed_at')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    
    // Метаданные
    $table->json('metadata')->nullable();
    $table->json('property_types')->nullable();
    $table->json('min_prices')->nullable();           // Цены по назначениям
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['city_id', 'is_active']);
    $table->index(['builder_id', 'is_active']);
    $table->index(['data_source', 'parsed_at']);
    $table->fullText(['name', 'address']);
});
```

#### commercial_block_subways (Связь коммерции и метро)

```php
Schema::create('commercial_block_subways', function (Blueprint $table) {
    $table->id();
    $table->foreignId('commercial_block_id')->constrained()->onDelete('cascade');
    $table->foreignId('subway_id')->constrained()->onDelete('cascade');
    $table->integer('distance_time')->nullable();
    $table->integer('distance_type_id')->nullable();
    $table->integer('priority')->default(500);
    $table->timestamps();
    
    $table->unique(['commercial_block_id', 'subway_id']);
});
```

---

### 3. Изображения (Polymorphic)

#### images (Полиморфная таблица изображений)

```php
Schema::create('images', function (Blueprint $table) {
    $table->id();
    
    // Полиморфная связь
    $table->morphs('imageable');                      // imageable_type, imageable_id
    
    // Данные изображения
    $table->string('external_id')->nullable()->index(); // MongoDB _id
    $table->string('path')->nullable();               // Путь на CDN
    $table->string('file_name');                     // Имя файла
    $table->string('url_thumbnail')->nullable();     // URL миниатюры
    $table->string('url_full')->nullable();          // URL полного изображения
    $table->string('alt')->nullable();
    $table->string('title')->nullable();
    $table->text('description')->nullable();
    
    // Метаданные
    $table->integer('width')->nullable();
    $table->integer('height')->nullable();
    $table->unsignedBigInteger('size')->nullable();  // Размер в байтах
    $table->string('mime_type')->nullable();
    
    // Локальное хранилище (если загружено)
    $table->string('local_path')->nullable();
    $table->string('disk')->default('public');
    
    // Сортировка
    $table->integer('sort_order')->default(0);
    $table->boolean('is_main')->default(false);      // Главное изображение
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['imageable_type', 'imageable_id', 'sort_order']);
    $table->index(['is_main', 'imageable_type']);
});
```

---

### 4. Источники данных и логирование

#### data_sources (Логи источников данных)

```php
Schema::create('data_sources', function (Blueprint $table) {
    $table->id();
    $table->enum('source_type', ['parser', 'manual', 'feed', 'import']);
    $table->string('source_name')->nullable();       // Название источника
    $table->string('source_file')->nullable();       // Имя файла (для feed)
    $table->morphs('sourceable');                    // Что было создано/обновлено
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->json('metadata')->nullable();            // Дополнительные данные
    $table->timestamp('processed_at');
    $table->timestamps();
    
    $table->index(['source_type', 'processed_at']);
    $table->index(['sourceable_type', 'sourceable_id']);
});
```

---

## 🎯 Модели

### Базовая модель с общими методами

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;

abstract class BaseTrendModel extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at', 'parsed_at', 'last_synced_at'];
    
    // Касты
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_exclusive' => 'boolean',
        'parsed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
    
    // Связь с изображениями (полиморфная)
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }
    
    // Главное изображение
    public function mainImage()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->where('is_main', true)
            ->orderBy('sort_order');
    }
    
    // Связь с источниками данных
    public function dataSources()
    {
        return $this->morphMany(DataSource::class, 'sourceable')->latest();
    }
    
    // Scope для активных
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    // Scope для источника данных
    public function scopeFromSource($query, string $source)
    {
        return $query->where('data_source', $source);
    }
    
    // Scope для парсера
    public function scopeFromParser($query)
    {
        return $query->where('data_source', 'parser');
    }
}
```

### Пример модели Block

```php
namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    
    // Отношения
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function subways(): BelongsToMany
    {
        return $this->belongsToMany(Subway::class, 'block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'distance_type', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
    
    public function prices(): HasMany
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

---

## 🔍 Фильтры

### BlockFilter

```php
namespace App\Http\Filters;

use App\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class BlockFilter extends AbstractFilter
{
    protected function getCallbacks(): array
    {
        return [
            'city_id' => [$this, 'cityId'],
            'region_id' => [$this, 'regionId'],
            'location_id' => [$this, 'locationId'],
            'builder_id' => [$this, 'builderId'],
            'is_exclusive' => [$this, 'exclusive'],
            'is_active' => [$this, 'active'],
            'min_price' => [$this, 'minPrice'],
            'max_price' => [$this, 'maxPrice'],
            'deadline' => [$this, 'deadline'],
            'finishing' => [$this, 'finishing'],
            'data_source' => [$this, 'dataSource'],
            'search' => [$this, 'search'],
            'subway_id' => [$this, 'subway'],
            'sort' => [$this, 'sort'],
        ];
    }
    
    protected function cityId(Builder $builder, $value)
    {
        $builder->where('city_id', $value);
    }
    
    protected function regionId(Builder $builder, $value)
    {
        $builder->where('region_id', $value);
    }
    
    protected function locationId(Builder $builder, $value)
    {
        $builder->where('location_id', $value);
    }
    
    protected function builderId(Builder $builder, $value)
    {
        $builder->where('builder_id', $value);
    }
    
    protected function exclusive(Builder $builder, $value)
    {
        $builder->where('is_exclusive', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function active(Builder $builder, $value)
    {
        $builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function minPrice(Builder $builder, $value)
    {
        $builder->where('min_price', '>=', $value * 100); // Конвертируем в копейки
    }
    
    protected function maxPrice(Builder $builder, $value)
    {
        $builder->where('max_price', '<=', $value * 100);
    }
    
    protected function deadline(Builder $builder, $value)
    {
        $builder->where('deadline', 'like', "%{$value}%");
    }
    
    protected function finishing(Builder $builder, $value)
    {
        $builder->where('finishing', $value);
    }
    
    protected function dataSource(Builder $builder, $value)
    {
        $builder->where('data_source', $value);
    }
    
    protected function search(Builder $builder, $value)
    {
        $builder->where(function($query) use ($value) {
            $query->whereFullText(['name', 'address'], $value)
                ->orWhere('name', 'like', "%{$value}%")
                ->orWhere('address', 'like', "%{$value}%");
        });
    }
    
    protected function subway(Builder $builder, $value)
    {
        $builder->whereHas('subways', function($query) use ($value) {
            $query->where('subways.id', $value);
        });
    }
    
    protected function sort(Builder $builder, $value)
    {
        $direction = $this->getQueryParam('sort_direction', 'asc');
        
        match($value) {
            'price' => $builder->orderBy('min_price', $direction),
            'name' => $builder->orderBy('name', $direction),
            'deadline' => $builder->orderBy('deadline_date', $direction),
            'created' => $builder->orderBy('created_at', $direction),
            default => $builder->orderBy('created_at', 'desc'),
        };
    }
    
    protected function before(Builder $builder)
    {
        // По умолчанию показываем только активные
        if (!$this->getQueryParam('include_inactive')) {
            $builder->active();
        }
    }
}
```

---

## 📦 Ресурсы

### BlockResource

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guid' => $this->guid,
            'name' => $this->name,
            'address' => $this->address,
            'external_id' => $this->external_id,
            
            // Связи
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'builder' => new BuilderResource($this->whenLoaded('builder')),
            'subways' => SubwayResource::collection($this->whenLoaded('subways')),
            'prices' => BlockPriceResource::collection($this->whenLoaded('prices')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => new ImageResource($this->whenLoaded('mainImage')),
            
            // Координаты
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            
            // Цены
            'prices' => [
                'min' => $this->min_price,
                'max' => $this->max_price,
                'min_formatted' => $this->formatted_min_price,
                'max_formatted' => $this->formatted_max_price,
            ],
            
            // Статистика
            'stats' => [
                'apartments_count' => $this->apartments_count,
                'view_apartments_count' => $this->view_apartments_count,
                'exclusive_apartments_count' => $this->exclusive_apartments_count,
            ],
            
            // Статусы
            'status' => $this->status,
            'is_suite' => $this->is_suite,
            'is_exclusive' => $this->is_exclusive,
            'is_marked' => $this->is_marked,
            'is_active' => $this->is_active,
            
            // Сроки
            'deadline' => $this->deadline,
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'finishing' => $this->finishing,
            
            // Источник данных
            'data_source' => $this->data_source,
            'parsed_at' => $this->parsed_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            
            // Метаданные
            'metadata' => $this->metadata,
            'advantages' => $this->advantages,
            'payment_types' => $this->payment_types,
            'contract_types' => $this->contract_types,
            
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

---

## ✅ Запросы (FormRequest)

### StoreBlockRequest

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Block::class);
    }
    
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            
            'guid' => ['required', 'string', 'max:255', 'unique:blocks,guid'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'crm_id' => ['nullable', 'integer'],
            'external_id' => ['nullable', 'string', 'max:255'],
            
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            'status' => ['nullable', 'integer'],
            'is_suite' => ['nullable', 'boolean'],
            'is_exclusive' => ['nullable', 'boolean'],
            'is_marked' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            
            'deadline' => ['nullable', 'string', 'max:255'],
            'deadline_date' => ['nullable', 'date'],
            'finishing' => ['nullable', 'string', 'max:255'],
            
            'data_source' => ['nullable', 'in:parser,manual,feed,import'],
            
            'metadata' => ['nullable', 'array'],
            'advantages' => ['nullable', 'array'],
            'payment_types' => ['nullable', 'array'],
            'contract_types' => ['nullable', 'array'],
            
            // Связи
            'subway_ids' => ['nullable', 'array'],
            'subway_ids.*' => ['exists:subways,id'],
        ];
    }
    
    protected function prepareForValidation()
    {
        // Если не указан источник, ставим manual
        if (!$this->has('data_source')) {
            $this->merge(['data_source' => 'manual']);
        }
        
        // Если не указан is_active, ставим true
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
```

### UpdateBlockRequest

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('block'));
    }
    
    public function rules(): array
    {
        $blockId = $this->route('block')->id;
        
        return [
            'city_id' => ['sometimes', 'exists:cities,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            
            'guid' => ['sometimes', 'string', 'max:255', Rule::unique('blocks', 'guid')->ignore($blockId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'crm_id' => ['nullable', 'integer'],
            'external_id' => ['nullable', 'string', 'max:255'],
            
            // ... остальные поля аналогично StoreBlockRequest
        ];
    }
}
```

---

## 🔗 Использование в контроллере

```php
namespace App\Http\Controllers\Api;

use App\Http\Filters\BlockFilter;
use App\Http\Requests\StoreBlockRequest;
use App\Http\Requests\UpdateBlockRequest;
use App\Http\Resources\BlockResource;
use App\Models\Block;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request)
    {
        $blocks = Block::query()
            ->with(['city', 'region', 'location', 'builder', 'subways', 'mainImage'])
            ->filter(new BlockFilter($request->all()))
            ->paginate($request->get('per_page', 15));
        
        return BlockResource::collection($blocks);
    }
    
    public function store(StoreBlockRequest $request)
    {
        $block = Block::create($request->validated());
        
        // Синхронизация связей
        if ($request->has('subway_ids')) {
            $block->subways()->sync($request->subway_ids);
        }
        
        // Создание записи об источнике
        $block->dataSources()->create([
            'source_type' => $request->data_source ?? 'manual',
            'source_name' => 'API',
            'user_id' => $request->user()->id,
            'processed_at' => now(),
        ]);
        
        return new BlockResource($block->load(['city', 'builder', 'mainImage']));
    }
    
    public function show(Block $block)
    {
        $block->load(['city', 'region', 'location', 'builder', 'subways', 'prices', 'images']);
        return new BlockResource($block);
    }
    
    public function update(UpdateBlockRequest $request, Block $block)
    {
        $block->update($request->validated());
        
        if ($request->has('subway_ids')) {
            $block->subways()->sync($request->subway_ids);
        }
        
        return new BlockResource($block->load(['city', 'builder', 'mainImage']));
    }
    
    public function destroy(Block $block)
    {
        $block->delete();
        return response()->json(['message' => 'Block deleted successfully']);
    }
}
```

---

## 📝 Примечания

1. **Индексы:** Все внешние ключи и часто используемые поля должны быть проиндексированы
2. **Soft Deletes:** Используются для мягкого удаления с возможностью восстановления
3. **Полиморфные связи:** Изображения используют полиморфную связь для гибкости
4. **Источники данных:** Поле `data_source` и таблица `data_sources` для отслеживания происхождения
5. **Цены:** Хранятся в копейках (integer) для точности
6. **Координаты:** Decimal для точности географических координат
7. **JSON поля:** Используются для гибких структур данных (metadata, advantages и т.д.)

---

**Документ будет продолжен с полными миграциями в следующем сообщении...**

