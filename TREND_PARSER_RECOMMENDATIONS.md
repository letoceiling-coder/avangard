# Рекомендации по расширению и улучшению парсера TrendAgent API

Документ содержит практические рекомендации для создания расширенного, масштабируемого и поддерживаемого парсера TrendAgent.ru API.

**Дата создания:** 2025-12-28  
**Основа:** Анализ текущей архитектуры, всех API endpoints и структур данных

---

## 📋 Содержание

1. [Архитектурные рекомендации](#архитектурные-рекомендации)
2. [Структура классов и сервисов](#структура-классов-и-сервисов)
3. [Обработка данных и трансформация](#обработка-данных-и-трансформация)
4. [Работа с изображениями](#работа-с-изображениями)
5. [Пагинация и массовая загрузка](#пагинация-и-массовая-загрузка)
6. [Кэширование](#кэширование)
7. [Обработка ошибок и логирование](#обработка-ошибок-и-логирование)
8. [Тестирование](#тестирование)
9. [Производительность](#производительность)
10. [Безопасность](#безопасность)

---

## 🏗️ Архитектурные рекомендации

### 1. Использование паттерна Strategy для разных типов объектов

**Проблема:** Текущий код имеет условную логику для разных типов объектов в одном методе.

**Решение:** Создать отдельные классы-стратегии для каждого типа объекта:

```php
namespace App\Services\TrendParser\Strategies;

interface ParserStrategyInterface
{
    public function search(array $params): array;
    public function getEndpoint(): string;
    public function normalizeResponse(array $response): array;
    public function getDefaultParams(): array;
}

class ApartmentsParserStrategy implements ParserStrategyInterface
{
    public function search(array $params): array { /* ... */ }
    public function getEndpoint(): string {
        return 'https://api.trendagent.ru/v4_29/blocks/search/';
    }
    // ...
}

class ParkingsParserStrategy implements ParserStrategyInterface
{
    public function search(array $params): array { /* ... */ }
    public function getEndpoint(): string {
        return 'https://parkings.trendagent.ru/search/places/';
    }
    // ...
}

class HousesParserStrategy implements ParserStrategyInterface { /* ... */ }
class CommercialParserStrategy implements ParserStrategyInterface { /* ... */ }
```

**Преимущества:**
- Легко добавлять новые типы объектов
- Каждый класс отвечает за один тип
- Проще тестировать
- Соблюдение принципа Single Responsibility

### 2. Использование Factory для создания стратегий

```php
namespace App\Services\TrendParser\Factories;

class ParserStrategyFactory
{
    public static function create(string $objectType): ParserStrategyInterface
    {
        return match($objectType) {
            'apartments' => new ApartmentsParserStrategy(),
            'parking' => new ParkingsParserStrategy(),
            'plots', 'houses' => new HousesParserStrategy(),
            'commercial' => new CommercialParserStrategy(),
            default => throw new \InvalidArgumentException("Unknown object type: {$objectType}"),
        };
    }
}
```

### 3. Разделение ответственности на слои

```
┌─────────────────────────────────────┐
│   Controllers (API endpoints)       │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Parser Service (Orchestration)    │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Strategy Classes (Parser logic)   │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   ApiAuth Service (HTTP requests)   │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Transformers (Data normalization) │
└─────────────────────────────────────┘
```

---

## 🔧 Структура классов и сервисов

### Предлагаемая структура папок

```
app/Services/TrendParser/
├── Contracts/
│   ├── ParserStrategyInterface.php
│   └── DataTransformerInterface.php
├── Strategies/
│   ├── ApartmentsParserStrategy.php
│   ├── ParkingsParserStrategy.php
│   ├── HousesParserStrategy.php
│   └── CommercialParserStrategy.php
├── Transformers/
│   ├── BlockTransformer.php
│   ├── ParkingTransformer.php
│   ├── VillageTransformer.php
│   └── CommercialTransformer.php
├── Factories/
│   └── ParserStrategyFactory.php
├── Builders/
│   ├── QueryBuilder.php
│   └── UrlBuilder.php
├── Exceptions/
│   ├── ParserException.php
│   ├── AuthenticationException.php
│   └── DataTransformationException.php
└── TrendParserService.php
```

### Основной сервис парсера

```php
namespace App\Services\TrendParser;

use App\Services\TrendParser\Factories\ParserStrategyFactory;
use App\Services\TrendParser\Contracts\ParserStrategyInterface;
use App\Services\TrendSsoApiAuth;

class TrendParserService
{
    private TrendSsoApiAuth $auth;
    private ?ParserStrategyInterface $strategy = null;

    public function __construct(TrendSsoApiAuth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Установить стратегию парсинга
     */
    public function setStrategy(string $objectType): self
    {
        $this->strategy = ParserStrategyFactory::create($objectType);
        return $this;
    }

    /**
     * Поиск объектов
     */
    public function search(array $params = []): array
    {
        if (!$this->strategy) {
            throw new \RuntimeException('Strategy not set. Call setStrategy() first.');
        }

        // Убеждаемся, что авторизация выполнена
        if (!$this->auth->isAuthenticated()) {
            throw new AuthenticationException('Not authenticated');
        }

        // Объединяем параметры по умолчанию с переданными
        $mergedParams = array_merge(
            $this->strategy->getDefaultParams(),
            $params
        );

        // Выполняем запрос через стратегию
        $response = $this->strategy->search($mergedParams);

        // Нормализуем ответ
        return $this->strategy->normalizeResponse($response);
    }

    /**
     * Получить все результаты с пагинацией
     */
    public function getAllResults(array $params = [], ?callable $onPageCallback = null): \Generator
    {
        $offset = 0;
        $count = $params['count'] ?? 100;
        $hasMore = true;

        while ($hasMore) {
            $params['offset'] = $offset;
            $results = $this->search($params);

            if (empty($results['data'])) {
                break;
            }

            if ($onPageCallback) {
                $onPageCallback($results);
            }

            yield $results;

            $hasMore = $results['pagination']['has_more'] ?? false;
            $offset += $count;
        }
    }
}
```

---

## 🔄 Обработка данных и трансформация

### Использование Transformers для нормализации данных

**Проблема:** Разные типы объектов имеют разную структуру ответов.

**Решение:** Создать трансформеры для приведения к единому формату:

```php
namespace App\Services\TrendParser\Transformers;

interface DataTransformerInterface
{
    public function transform(array $data): array;
    public function transformCollection(array $items): array;
}

class BlockTransformer implements DataTransformerInterface
{
    private ImageUrlBuilder $imageBuilder;

    public function __construct(ImageUrlBuilder $imageBuilder)
    {
        $this->imageBuilder = $imageBuilder;
    }

    public function transform(array $block): array
    {
        return [
            'id' => $block['_id'],
            'crm_id' => $block['crm_id'] ?? null,
            'name' => $block['name'],
            'guid' => $block['guid'],
            'address' => $this->transformAddress($block['address'] ?? []),
            'city' => $this->transformLocation($block['city'] ?? []),
            'region' => $this->transformLocation($block['region'] ?? []),
            'location' => $this->transformLocation($block['location'] ?? []),
            'builder' => $this->transformBuilder($block['builder'] ?? []),
            'coordinates' => [
                'latitude' => $block['latitude'] ?? null,
                'longitude' => $block['longitude'] ?? null,
            ],
            'subways' => $this->transformSubways($block['subways'] ?? []),
            'prices' => $this->transformPrices($block),
            'deadline' => $this->transformDeadline($block),
            'finishing' => $block['finishing'] ?? null,
            'images' => $this->transformImages($block['image'] ?? null),
            'stats' => [
                'apartments_count' => $block['apart_count'] ?? 0,
                'view_apartments_count' => $block['view_apart_count'] ?? 0,
                'exclusive_apartments_count' => $block['exclusive_apartments_count'] ?? 0,
            ],
            'metadata' => [
                'status' => $block['status'] ?? null,
                'is_exclusive' => $block['exclusive'] ?? false,
                'is_suite' => $block['is_suite'] ?? false,
                'marked' => $block['marked'] ?? false,
            ],
        ];
    }

    public function transformCollection(array $blocks): array
    {
        return array_map([$this, 'transform'], $blocks);
    }

    private function transformAddress(array $address): ?string
    {
        return !empty($address) ? implode(', ', $address) : null;
    }

    private function transformLocation(array $location): ?array
    {
        if (empty($location)) {
            return null;
        }

        return [
            'id' => $location['_id'] ?? null,
            'guid' => $location['guid'] ?? null,
            'name' => $location['name'] ?? null,
            'crm_id' => $location['crm_id'] ?? null,
        ];
    }

    private function transformBuilder(array $builder): ?array
    {
        return $this->transformLocation($builder); // Такая же структура
    }

    private function transformSubways(array $subways): array
    {
        return array_map(function($subway) {
            return [
                'id' => $subway['_id'] ?? null,
                'name' => $subway['name'] ?? null,
                'guid' => $subway['guid'] ?? null,
                'line' => $subway['line'] ?? null,
                'line_number' => $subway['line_number'] ?? null,
                'color' => $subway['color'] ?? null,
                'distance' => [
                    'time' => $subway['distance_timing'] ?? null,
                    'type' => $subway['distance_type'] ?? null,
                    'type_id' => $subway['distance_type_id'] ?? null,
                ],
                'coordinates' => $this->extractCoordinates($subway['geometry'] ?? null),
            ];
        }, $subways);
    }

    private function transformPrices(array $block): array
    {
        return [
            'min' => $block['min_price'] ?? null,
            'max' => $block['max_price'] ?? null,
            'by_type' => $this->transformMinPrices($block['min_prices'] ?? []),
        ];
    }

    private function transformMinPrices(array $minPrices): array
    {
        return array_map(function($price) {
            return [
                'room_type_id' => $price['room'] ?? null,
                'room_type_name' => $price['rooms'] ?? null,
                'price' => $price['price'] ?? null,
            ];
        }, $minPrices);
    }

    private function transformDeadline(array $block): ?array
    {
        if (empty($block['deadline'])) {
            return null;
        }

        return [
            'text' => $block['deadline'],
            'details' => $this->transformDeadlineExt($block['deadline_ext'] ?? []),
        ];
    }

    private function transformDeadlineExt(array $deadlineExt): array
    {
        return array_map(function($item) {
            return [
                'deadline' => $item['deadline'] ?? null,
                'overdue_check' => $item['deadline_over_check'] ?? false,
            ];
        }, $deadlineExt);
    }

    private function transformImages(?array $image): ?array
    {
        if (empty($image)) {
            return null;
        }

        return [
            'id' => $image['_id'] ?? null,
            'file_name' => $image['file_name'] ?? null,
            'path' => $image['path'] ?? null,
            'url_thumbnail' => $this->imageBuilder->buildThumbnailUrl($image),
            'url_full' => $this->imageBuilder->buildFullUrl($image),
        ];
    }

    private function extractCoordinates(?array $geometry): ?array
    {
        if (empty($geometry) || $geometry['type'] !== 'Point') {
            return null;
        }

        $coords = $geometry['coordinates'] ?? [];
        return [
            'longitude' => $coords[0] ?? null,
            'latitude' => $coords[1] ?? null,
        ];
    }
}
```

### Единый формат ответа

Все трансформеры должны приводить данные к единому формату:

```php
[
    'id' => string,
    'type' => 'apartment' | 'parking' | 'house' | 'commercial',
    'name' => string,
    'address' => string,
    'location' => [
        'city' => [...],
        'region' => [...],
        'coordinates' => [...],
    ],
    'builder' => [...],
    'prices' => [...],
    'images' => [...],
    'metadata' => [...],
]
```

---

## 🖼️ Работа с изображениями

### Отдельный класс для работы с изображениями

```php
namespace App\Services\TrendParser\Builders;

class ImageUrlBuilder
{
    private const CDN_BASE_URL = 'https://selcdn.trendagent.ru/images';

    /**
     * Построить URL миниатюры
     */
    public function buildThumbnailUrl(array $image): ?string
    {
        if (empty($image['path']) || empty($image['file_name'])) {
            return null;
        }

        $path = $this->normalizePath($image['path']);
        $fileName = $image['file_name'];

        return sprintf(
            '%s/%s/m_%s',
            self::CDN_BASE_URL,
            $path,
            $fileName
        );
    }

    /**
     * Построить URL полного изображения
     */
    public function buildFullUrl(array $image): ?string
    {
        if (empty($image['path']) || empty($image['file_name'])) {
            return null;
        }

        $path = $this->normalizePath($image['path']);
        $fileName = $image['file_name'];

        return sprintf(
            '%s/%s/%s',
            self::CDN_BASE_URL,
            $path,
            $fileName
        );
    }

    /**
     * Нормализовать путь (убрать лишние слэши)
     */
    private function normalizePath(string $path): string
    {
        return trim($path, '/');
    }

    /**
     * Обработать массив изображений
     */
    public function transformImageArray(?array $images): array
    {
        if (empty($images)) {
            return [];
        }

        // Если это один объект, делаем массив
        if (isset($images['file_name'])) {
            $images = [$images];
        }

        return array_map(function($image) {
            return [
                'id' => $image['_id'] ?? null,
                'file_name' => $image['file_name'] ?? null,
                'path' => $image['path'] ?? null,
                'url_thumbnail' => $this->buildThumbnailUrl($image),
                'url_full' => $this->buildFullUrl($image),
            ];
        }, $images);
    }
}
```

### Загрузка изображений (опционально)

```php
namespace App\Services\TrendParser;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ImageDownloader
{
    /**
     * Загрузить изображение и сохранить локально
     */
    public function download(string $url, string $storagePath = 'trend_images'): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $fileName = basename(parse_url($url, PHP_URL_PATH));
            $fullPath = "{$storagePath}/{$fileName}";

            Storage::disk('local')->put($fullPath, $response->body());

            return $fullPath;
        } catch (\Exception $e) {
            \Log::error('Failed to download image', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Загрузить несколько изображений
     */
    public function downloadBatch(array $urls, string $storagePath = 'trend_images'): array
    {
        $results = [];
        foreach ($urls as $url) {
            $results[$url] = $this->download($url, $storagePath);
        }
        return $results;
    }
}
```

---

## 📄 Пагинация и массовая загрузка

### Класс для работы с пагинацией

```php
namespace App\Services\TrendParser;

class Paginator
{
    private int $count;
    private int $offset;
    private bool $hasMore;
    private int $total;

    public function __construct(int $count = 100, int $offset = 0)
    {
        $this->count = $count;
        $this->offset = $offset;
        $this->hasMore = true;
        $this->total = 0;
    }

    /**
     * Получить следующий offset
     */
    public function next(): int
    {
        $this->offset += $this->count;
        return $this->offset;
    }

    /**
     * Обновить состояние пагинатора из ответа API
     */
    public function updateFromResponse(array $response): void
    {
        $returnedCount = count($response['data'] ?? []);
        $this->hasMore = $returnedCount >= $this->count;
        $this->total = $response['total'] ?? $this->total + $returnedCount;
    }

    /**
     * Проверить, есть ли еще данные
     */
    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * Получить текущие параметры для запроса
     */
    public function getParams(): array
    {
        return [
            'count' => $this->count,
            'offset' => $this->offset,
        ];
    }
}
```

### Массовая загрузка с обработкой ошибок

```php
class TrendParserService
{
    /**
     * Получить все результаты с обработкой ошибок
     */
    public function getAllResultsSafe(
        array $params = [],
        ?callable $onProgress = null,
        int $maxRetries = 3
    ): \Generator {
        $paginator = new Paginator($params['count'] ?? 100);
        $retryCount = 0;

        while ($paginator->hasMore()) {
            try {
                $params = array_merge($params, $paginator->getParams());
                $results = $this->search($params);

                $paginator->updateFromResponse($results);
                
                if ($onProgress) {
                    $onProgress($results, $paginator);
                }

                yield $results;
                $retryCount = 0; // Сбрасываем счетчик при успехе

            } catch (\Exception $e) {
                $retryCount++;
                
                if ($retryCount >= $maxRetries) {
                    \Log::error('Max retries reached', [
                        'error' => $e->getMessage(),
                        'offset' => $paginator->getParams()['offset'],
                    ]);
                    throw $e;
                }

                // Задержка перед повтором
                sleep(pow(2, $retryCount)); // Exponential backoff: 2s, 4s, 8s
                continue;
            }
        }
    }
}
```

---

## 💾 Кэширование

### Кэширование токенов авторизации

```php
namespace App\Services\TrendParser;

use Illuminate\Support\Facades\Cache;

class TokenCache
{
    private const CACHE_PREFIX = 'trend_auth_token';
    private const TTL = 300; // 5 минут (токены обычно живут 5 минут)

    public function get(string $key): ?string
    {
        return Cache::get($this->buildKey($key));
    }

    public function put(string $key, string $token, ?int $ttl = null): void
    {
        Cache::put(
            $this->buildKey($key),
            $token,
            $ttl ?? self::TTL
        );
    }

    public function forget(string $key): void
    {
        Cache::forget($this->buildKey($key));
    }

    private function buildKey(string $key): string
    {
        return self::CACHE_PREFIX . ':' . md5($key);
    }
}
```

### Кэширование справочников

```php
class DirectoryCache
{
    private const CACHE_PREFIX = 'trend_directories';
    private const TTL = 3600; // 1 час

    /**
     * Получить справочник из кэша или API
     */
    public function remember(string $type, callable $callback): array
    {
        $key = $this->buildKey($type);
        
        return Cache::remember($key, self::TTL, function() use ($callback) {
            return $callback();
        });
    }

    /**
     * Очистить кэш справочников
     */
    public function clear(): void
    {
        Cache::flush(); // Или более селективная очистка
    }

    private function buildKey(string $type): string
    {
        return self::CACHE_PREFIX . ':' . $type;
    }
}
```

---

## 🚨 Обработка ошибок и логирование

### Кастомные исключения

```php
namespace App\Services\TrendParser\Exceptions;

class ParserException extends \Exception
{
    private ?array $context;

    public function __construct(string $message, ?array $context = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }
}

class AuthenticationException extends ParserException {}
class RateLimitException extends ParserException {}
class InvalidResponseException extends ParserException {}
```

### Централизованное логирование

```php
namespace App\Services\TrendParser;

use Illuminate\Support\Facades\Log;

class ParserLogger
{
    private const CHANNEL = 'trend_parser';

    public function logRequest(string $endpoint, array $params): void
    {
        Log::channel(self::CHANNEL)->info('API Request', [
            'endpoint' => $endpoint,
            'params' => $this->sanitizeParams($params),
        ]);
    }

    public function logResponse(string $endpoint, array $response): void
    {
        Log::channel(self::CHANNEL)->info('API Response', [
            'endpoint' => $endpoint,
            'results_count' => count($response['data'] ?? []),
            'status_code' => $response['status_code'] ?? null,
        ]);
    }

    public function logError(\Throwable $e, ?array $context = null): void
    {
        Log::channel(self::CHANNEL)->error('Parser Error', [
            'message' => $e->getMessage(),
            'class' => get_class($e),
            'context' => $context,
            'trace' => $e->getTraceAsString(),
        ]);
    }

    private function sanitizeParams(array $params): array
    {
        // Удаляем чувствительные данные
        unset($params['auth_token'], $params['password']);
        return $params;
    }
}
```

### Настройка канала логирования в `config/logging.php`

```php
'channels' => [
    // ...
    'trend_parser' => [
        'driver' => 'daily',
        'path' => storage_path('logs/trend_parser.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

---

## 🧪 Тестирование

### Unit тесты для трансформеров

```php
namespace Tests\Unit\Services\TrendParser\Transformers;

use App\Services\TrendParser\Transformers\BlockTransformer;
use App\Services\TrendParser\Builders\ImageUrlBuilder;
use Tests\TestCase;

class BlockTransformerTest extends TestCase
{
    private BlockTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new BlockTransformer(new ImageUrlBuilder());
    }

    public function test_transform_basic_block(): void
    {
        $block = [
            '_id' => 'test_id',
            'name' => 'Test Block',
            'guid' => 'test-guid',
            'address' => ['Test Street 1'],
            'latitude' => 55.75,
            'longitude' => 37.61,
        ];

        $result = $this->transformer->transform($block);

        $this->assertEquals('test_id', $result['id']);
        $this->assertEquals('Test Block', $result['name']);
        $this->assertEquals('Test Street 1', $result['address']);
        $this->assertEquals(55.75, $result['coordinates']['latitude']);
        $this->assertEquals(37.61, $result['coordinates']['longitude']);
    }

    public function test_transform_with_images(): void
    {
        $block = [
            '_id' => 'test_id',
            'image' => [
                'path' => 'test/path',
                'file_name' => 'test.jpg',
            ],
        ];

        $result = $this->transformer->transform($block);

        $this->assertNotNull($result['images']);
        $this->assertStringContainsString('test.jpg', $result['images']['url_thumbnail']);
    }
}
```

### Feature тесты для парсера

```php
namespace Tests\Feature\Services\TrendParser;

use App\Services\TrendParser\TrendParserService;
use App\Services\TrendSsoApiAuth;
use Tests\TestCase;
use Mockery;

class TrendParserServiceTest extends TestCase
{
    public function test_search_apartments(): void
    {
        $auth = Mockery::mock(TrendSsoApiAuth::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        // ... другие моки

        $parser = new TrendParserService($auth);
        $parser->setStrategy('apartments');

        // Тестируем логику
    }
}
```

---

## ⚡ Производительность

### 1. Асинхронные запросы (если нужно)

Для массовой загрузки можно использовать async запросы через Guzzle:

```php
use GuzzleHttp\Promise;

class AsyncParser
{
    public function fetchMultiple(array $endpoints): array
    {
        $promises = [];
        
        foreach ($endpoints as $key => $url) {
            $promises[$key] = $this->client->getAsync($url);
        }

        $responses = Promise\Utils::settle($promises)->wait();

        $results = [];
        foreach ($responses as $key => $response) {
            if ($response['state'] === 'fulfilled') {
                $results[$key] = json_decode(
                    $response['value']->getBody()->getContents(),
                    true
                );
            }
        }

        return $results;
    }
}
```

### 2. Batch обработка

```php
class BatchProcessor
{
    public function processInBatches(
        array $items,
        callable $processor,
        int $batchSize = 50
    ): array {
        $batches = array_chunk($items, $batchSize);
        $results = [];

        foreach ($batches as $batch) {
            $batchResults = array_map($processor, $batch);
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }
}
```

### 3. Ограничение rate limiting

```php
class RateLimiter
{
    private int $requestsPerSecond;
    private float $lastRequestTime = 0;

    public function __construct(int $requestsPerSecond = 10)
    {
        $this->requestsPerSecond = $requestsPerSecond;
    }

    public function waitIfNeeded(): void
    {
        $minInterval = 1.0 / $this->requestsPerSecond;
        $timeSinceLastRequest = microtime(true) - $this->lastRequestTime;

        if ($timeSinceLastRequest < $minInterval) {
            usleep((int)(($minInterval - $timeSinceLastRequest) * 1000000));
        }

        $this->lastRequestTime = microtime(true);
    }
}
```

---

## 🔒 Безопасность

### 1. Валидация входных данных

```php
namespace App\Services\TrendParser\Validators;

class RequestValidator
{
    public function validateSearchParams(array $params): array
    {
        $rules = [
            'count' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'sort' => 'nullable|string|in:price,name,deadline',
            'sort_order' => 'nullable|string|in:asc,desc',
        ];

        $validator = \Validator::make($params, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException(
                'Invalid parameters: ' . $validator->errors()->first()
            );
        }

        return $validator->validated();
    }
}
```

### 2. Санитизация данных перед сохранением

```php
class DataSanitizer
{
    public function sanitize(array $data): array
    {
        // Удаляем потенциально опасные поля
        unset($data['auth_token'], $data['password']);

        // Очищаем HTML теги из текстовых полей
        $textFields = ['name', 'description', 'address'];
        foreach ($textFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = strip_tags($data[$field]);
            }
        }

        return $data;
    }
}
```

---

## 📝 Чеклист для реализации

### Фаза 1: Базовая архитектура
- [ ] Создать интерфейсы (ParserStrategyInterface, DataTransformerInterface)
- [ ] Реализовать Factory для создания стратегий
- [ ] Создать базовые стратегии для каждого типа объектов
- [ ] Создать основной TrendParserService

### Фаза 2: Трансформация данных
- [ ] Создать ImageUrlBuilder
- [ ] Реализовать трансформеры для каждого типа
- [ ] Привести все данные к единому формату

### Фаза 3: Расширенные возможности
- [ ] Реализовать пагинацию
- [ ] Добавить массовую загрузку
- [ ] Реализовать кэширование

### Фаза 4: Надежность
- [ ] Добавить обработку ошибок
- [ ] Реализовать логирование
- [ ] Добавить retry механизм

### Фаза 5: Оптимизация
- [ ] Добавить rate limiting
- [ ] Оптимизировать запросы
- [ ] Добавить кэширование справочников

---

## 🎯 Приоритеты

1. **Высокий приоритет:**
   - Архитектура с использованием Strategy паттерна
   - Единый формат данных через трансформеры
   - Обработка изображений

2. **Средний приоритет:**
   - Пагинация и массовая загрузка
   - Кэширование токенов
   - Логирование

3. **Низкий приоритет:**
   - Асинхронные запросы
   - Загрузка изображений
   - Дополнительная оптимизация

---

**Документ создан на основе анализа текущей архитектуры и всех API endpoints**

