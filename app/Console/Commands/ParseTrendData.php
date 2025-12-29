<?php

namespace App\Console\Commands;

use App\Helpers\TrendSettings;
use App\Models\Trend\City;
use App\Services\TrendDataSyncService;
use App\Services\TrendSsoApiAuth;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ParseTrendData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trend:parse 
                            {--type=* : Типы объектов для парсинга (blocks, parkings, villages, plots, commercial-blocks, commercial-premises)}
                            {--city=* : GUID городов для парсинга (если не указано, берутся все активные)}
                            {--check-images : Проверять доступность изображений}
                            {--force : Принудительное обновление всех объектов}
                            {--limit=1000 : Лимит объектов на тип (по умолчанию 1000)}
                            {--offset=0 : Смещение для пагинации}
                            {--skip-errors : Пропускать ошибки и продолжать}
                            {--phone= : Телефон для авторизации (если не указан, используется из настроек)}
                            {--password= : Пароль для авторизации (если не указан, используется из настроек)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг данных из TrendAgent API для всех типов объектов';

    /**
     * Типы объектов и их настройки
     */
    protected array $objectTypes = [
        'blocks' => [
            'name' => 'Блоки (Квартиры)',
            'endpoint' => 'https://api.trendagent.ru/v4_29/blocks/search/',
            'method' => 'syncBlock',
            'params' => ['city', 'lang', 'count', 'offset', 'sort', 'sort_order', 'show_type'],
        ],
        'parkings' => [
            'name' => 'Паркинги',
            'endpoint' => 'https://parkings.trendagent.ru/search/places/',
            'method' => 'syncBlock', // Используем syncBlock для паркингов, так как структура похожа
            'params' => ['city', 'lang', 'count'],
        ],
        'villages' => [
            'name' => 'Поселки (Дома с участками)',
            'endpoint' => 'https://house-api.trendagent.ru/v1/search/villages',
            'method' => 'syncVillage',
            'params' => ['city', 'lang', 'count', 'sort_type', 'sort_order'],
        ],
        'plots' => [
            'name' => 'Участки',
            'endpoint' => 'https://house-api.trendagent.ru/v1/search/plots', // Используем search вместо filter
            'method' => 'syncPlot',
            'params' => ['city', 'lang', 'count'],
        ],
        'commercial-blocks' => [
            'name' => 'Коммерческие объекты',
            'endpoint' => 'https://commerce.trendagent.ru/search/blocks/',
            'method' => 'syncCommercialBlock',
            'params' => ['city', 'lang', 'count', 'show_type', 'sort', 'sort_order'],
        ],
        'commercial-premises' => [
            'name' => 'Коммерческие помещения',
            'endpoint' => 'https://commerce.trendagent.ru/search/premises',
            'method' => 'syncCommercialPremise',
            'params' => ['city', 'lang', 'count'],
        ],
    ];

    protected TrendSsoApiAuth $auth;
    protected TrendDataSyncService $syncService;
    protected Client $httpClient;
    protected ?string $authToken = null;
    protected array $stats = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('🚀 Начало парсинга данных TrendAgent...');
        $this->newLine();

        // Инициализация сервисов
        $this->auth = new TrendSsoApiAuth();
        $this->syncService = new TrendDataSyncService();
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false,
        ]);

        // Авторизация
        if (!$this->authenticate()) {
            $executionTime = microtime(true) - $startTime;
            Log::warning('ParseTrendData: Failed authentication', [
                'execution_time_seconds' => round($executionTime, 2),
            ]);
            return 1;
        }

        // Получение списка городов
        $cities = $this->getCities();
        
        // Фильтруем города - используем только те, у которых есть external_id
        $cities = $cities->filter(function ($city) {
            return !empty($city->external_id);
        });
        
        if ($cities->isEmpty()) {
            $executionTime = microtime(true) - $startTime;
            $this->error('❌ Не найдено активных городов с external_id для парсинга. Выполните команду cities:update-external-id');
            Log::warning('ParseTrendData: No active cities with external_id found', [
                'execution_time_seconds' => round($executionTime, 2),
            ]);
            return 1;
        }

        $this->info("✅ Найдено городов с external_id: {$cities->count()}");
        $this->newLine();

        // Определение типов объектов для парсинга
        $typesToParse = $this->getTypesToParse();
        if (empty($typesToParse)) {
            $executionTime = microtime(true) - $startTime;
            $this->error('❌ Не указаны типы объектов для парсинга');
            $this->line('Доступные типы: ' . implode(', ', array_keys($this->objectTypes)));
            Log::warning('ParseTrendData: No object types specified', [
                'execution_time_seconds' => round($executionTime, 2),
            ]);
            return 1;
        }

        // Инициализация статистики
        $this->initStats($typesToParse);

        // Парсинг для каждого типа объектов
        foreach ($typesToParse as $type) {
            if (!isset($this->objectTypes[$type])) {
                $this->warn("⚠️  Неизвестный тип объекта: {$type}");
                continue;
            }

            $this->parseObjectType($type, $cities);
        }

        // Вывод итоговой статистики
        $this->displayStats();
        
        // Вычисление времени выполнения
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $executionTimeFormatted = $this->formatExecutionTime($executionTime);
        
        $this->newLine();
        $this->info("⏱️  Время выполнения: {$executionTimeFormatted}");
        $this->newLine();
        
        // Логирование времени выполнения
        Log::info('ParseTrendData: Parsing completed', [
            'execution_time_seconds' => round($executionTime, 2),
            'execution_time_formatted' => $executionTimeFormatted,
            'stats' => $this->stats,
        ]);
        
        // Сохраняем статистику для возможного использования в планировщике
        $this->lastRunStats = $this->stats;

        return 0;
    }

    /**
     * Авторизация в Trend SSO
     */
    protected function authenticate(): bool
    {
        // Используем значения из опций или из настроек (или значения по умолчанию)
        $phone = $this->option('phone') ?: TrendSettings::getPhone();
        $password = $this->option('password') ?: TrendSettings::getPassword();

        $this->info("🔐 Авторизация через Trend SSO...");

        try {
            $authData = $this->auth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                $this->error('❌ Авторизация не удалась');
                return false;
            }

            $this->authToken = $this->auth->getAuthToken();

            if (empty($this->authToken)) {
                $this->error('❌ Токен авторизации не найден');
                return false;
            }

            $this->info('✅ Авторизация успешна!');
            $this->newLine();

            return true;
        } catch (\Exception $e) {
            $this->error('❌ Ошибка авторизации: ' . $e->getMessage());
            Log::error('ParseTrendData: Authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Получить список городов для парсинга
     */
    protected function getCities()
    {
        $cityGuids = $this->option('city');
        
        // Получаем только города (не регионы), где region_id не NULL
        $query = City::where('is_active', true)
            ->whereNotNull('region_id'); // Только города, не регионы

        if (!empty($cityGuids)) {
            $query->whereIn('guid', $cityGuids);
        }

        return $query->get();
    }

    /**
     * Получить типы объектов для парсинга
     */
    protected function getTypesToParse(): array
    {
        $types = $this->option('type');

        if (empty($types)) {
            // Если не указаны, парсим все типы
            return array_keys($this->objectTypes);
        }

        return $types;
    }

    /**
     * Инициализация статистики
     */
    protected function initStats(array $types): void
    {
        foreach ($types as $type) {
            $this->stats[$type] = [
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
            ];
        }
    }

    /**
     * Парсинг конкретного типа объектов
     */
    protected function parseObjectType(string $type, $cities): void
    {
        $typeConfig = $this->objectTypes[$type];
        
        $this->info("📦 Парсинг: {$typeConfig['name']}");
        $this->line("   Эндпоинт: {$typeConfig['endpoint']}");

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $checkImages = $this->option('check-images');
        $force = $this->option('force');
        $skipErrors = $this->option('skip-errors');

        $options = [
            'skip_errors' => $skipErrors,
            'log_errors' => true,
            'update_existing' => true,
            'create_missing_references' => true,
            'track_changes' => true,
            'log_price_changes' => true,
            'check_images' => $checkImages,
            'force_update' => $force,
        ];

        $bar = $this->output->createProgressBar($cities->count());
        $bar->start();

        foreach ($cities as $city) {
            try {
                $this->parseCityObjects($type, $typeConfig, $city, $limit, $offset, $options);
            } catch (\Exception $e) {
                $this->stats[$type]['errors']++;
                
                Log::error("ParseTrendData: Error parsing {$type} for city {$city->guid}", [
                    'city_id' => $city->id,
                    'city_guid' => $city->guid,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                if (!$skipErrors) {
                    $bar->finish();
                    $this->newLine();
                    $this->error("❌ Ошибка парсинга {$type} для города {$city->name}: " . $e->getMessage());
                    return;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Парсинг объектов для конкретного города
     */
    protected function parseCityObjects(
        string $type,
        array $typeConfig,
        City $city,
        int $limit,
        int $offset,
        array $options
    ): void {
        $endpoint = $typeConfig['endpoint'];
        $method = $typeConfig['method'];
        
        // Пагинация: парсим все страницы до конца
        $currentOffset = $offset;
        $totalProcessed = 0;
        $hasMore = true;
        $page = 1;
        
        $this->line("   Город: {$city->name}");
        
        while ($hasMore) {
            $params = $this->buildParams($typeConfig['params'], $city, $limit, $currentOffset, $type);

            try {
                // Запрос к API
                $response = $this->httpClient->get($endpoint, [
                    'query' => $params,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->authToken,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 60, // Увеличиваем timeout для больших запросов
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

            // Определяем структуру ответа в зависимости от типа объекта
            // Для blocks: data.data.results или data.data
            // Для других: data.data или data.results
            $objects = null;
            
            if ($type === 'blocks' || $type === 'commercial-blocks') {
                // Для blocks API структура: {data: {data: {results: [...]}}}
                if (isset($data['data']['data']['results']) && is_array($data['data']['data']['results'])) {
                    $objects = $data['data']['data']['results'];
                } elseif (isset($data['data']['results']) && is_array($data['data']['results'])) {
                    $objects = $data['data']['results'];
                } elseif (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    $objects = $data['data']['data'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $objects = $data['data'];
                }
            } elseif ($type === 'villages') {
                // Для villages API структура: {list: [...]} или {data: {list: [...]}}
                if (isset($data['list']) && is_array($data['list'])) {
                    $objects = $data['list'];
                } elseif (isset($data['data']['list']) && is_array($data['data']['list'])) {
                    $objects = $data['data']['list'];
                } elseif (isset($data['data']['results']) && is_array($data['data']['results'])) {
                    $objects = $data['data']['results'];
                }
            } elseif ($type === 'plots') {
                // Для plots API возвращает фильтры, нужно использовать другой endpoint
                // Пока пропускаем, так как это endpoint для фильтров, а не для списка
                Log::warning("ParseTrendData: plots endpoint returns filters, not object list", [
                    'city_guid' => $city->guid,
                    'endpoint' => $endpoint,
                ]);
                return;
            } elseif ($type === 'parkings') {
                // Для parkings API структура: {data: [...]} или {results: [...]}
                if (isset($data['data']) && is_array($data['data'])) {
                    $objects = $data['data'];
                } elseif (isset($data['results']) && is_array($data['results'])) {
                    $objects = $data['results'];
                }
            } else {
                // Для других типов: data.data, data.results, result, или data
                if (isset($data['data']['results']) && is_array($data['data']['results'])) {
                    $objects = $data['data']['results'];
                } elseif (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    $objects = $data['data']['data'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $objects = $data['data'];
                } elseif (isset($data['results']) && is_array($data['results'])) {
                    $objects = $data['results'];
                } elseif (isset($data['result']) && is_array($data['result'])) {
                    // Для commercial-premises структура: {result: [...]}
                    $objects = $data['result'];
                }
            }

                if ($objects === null || !is_array($objects)) {
                    // Нет объектов на этой странице, завершаем пагинацию
                    if ($page === 1) {
                        // Если на первой странице нет объектов, логируем предупреждение
                        Log::warning("ParseTrendData: Invalid response structure for {$type}", [
                            'city_guid' => $city->guid,
                            'response_keys' => array_keys($data ?? []),
                            'has_data' => isset($data['data']),
                            'data_type' => isset($data['data']) ? gettype($data['data']) : 'not set',
                            'response_structure' => json_encode(array_slice($data ?? [], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                    $hasMore = false;
                    break;
                }

                $totalFound = count($objects);
                
                if ($totalFound === 0) {
                    // Нет объектов на этой странице, завершаем пагинацию
                    $hasMore = false;
                    break;
                }

                $this->info("   📄 Страница {$page}, offset {$currentOffset}: получено {$totalFound} объектов");
                $this->stats[$type]['total'] += $totalFound;

                // Синхронизация каждого объекта
                foreach ($objects as $objectData) {
                    try {
                        $syncMethod = $typeConfig['method'];
                        
                        if (!method_exists($this->syncService, $syncMethod)) {
                            throw new \Exception("Метод {$syncMethod} не существует в TrendDataSyncService");
                        }
                        
                        // Передаем город в опциях для методов синхронизации
                        $syncOptions = array_merge($options, [
                            'city' => $city,
                        ]);
                        
                        // Вызываем соответствующий метод синхронизации
                        $syncedObject = $this->syncService->$syncMethod($objectData, $syncOptions);

                        // Определяем, был ли объект создан или обновлен
                        // Используем сравнение created_at и updated_at (если они равны и очень свежие - значит создан)
                        $isNew = $syncedObject->created_at && 
                                 $syncedObject->updated_at && 
                                 $syncedObject->created_at->equalTo($syncedObject->updated_at) &&
                                 $syncedObject->created_at->isAfter(now()->subMinute());
                        
                        if ($isNew) {
                            $this->stats[$type]['created']++;
                        } else {
                            $this->stats[$type]['updated']++;
                        }
                        
                        $totalProcessed++;

                    } catch (\Exception $e) {
                        $this->stats[$type]['errors']++;
                        
                        Log::error("ParseTrendData: Error syncing object", [
                            'type' => $type,
                            'city_guid' => $city->guid,
                            'object_id' => $objectData['_id'] ?? $objectData['id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);

                        if (!$options['skip_errors']) {
                            throw $e;
                        }
                    }
                }
                
                // Проверяем, есть ли еще объекты для загрузки
                // Если получено меньше чем limit, значит это последняя страница
                if ($totalFound < $limit) {
                    $hasMore = false;
                } else {
                    // Переходим к следующей странице
                    $currentOffset += $limit;
                    $page++;
                    // Небольшая задержка между запросами, чтобы не перегружать API (0.1 секунды)
                    usleep(100000);
                }

            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("ParseTrendData: API request failed", [
                    'type' => $type,
                    'city_guid' => $city->guid,
                    'endpoint' => $endpoint,
                    'offset' => $currentOffset,
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
                
                if (!$options['skip_errors']) {
                    throw $e;
                } else {
                    // Пропускаем эту страницу и продолжаем
                    $currentOffset += $limit;
                    $page++;
                    // Если это была первая страница, прекращаем пагинацию
                    if ($page === 1) {
                        $hasMore = false;
                    }
                }
            } catch (\Exception $e) {
                Log::error("ParseTrendData: Error parsing {$type} for city {$city->guid}", [
                    'city_id' => $city->id,
                    'city_guid' => $city->guid,
                    'offset' => $currentOffset,
                    'page' => $page,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                if (!$options['skip_errors']) {
                    throw $e;
                } else {
                    // Пропускаем эту страницу и продолжаем
                    $currentOffset += $limit;
                    $page++;
                    // Если это была первая страница, прекращаем пагинацию
                    if ($page === 1) {
                        $hasMore = false;
                    }
                }
            }
        }
        
        $this->info("   ✅ Всего обработано объектов: {$totalProcessed} (страниц: " . ($page - 1) . ")");
    }

    /**
     * Построение параметров запроса
     */
    protected function buildParams(array $paramNames, City $city, int $limit, int $offset, string $objectType = ''): array
    {
        $params = [
            'lang' => 'ru',
            'count' => $limit,
        ];

        // Для blocks и commercial-blocks требуется show_type (обязательный параметр)
        if ($objectType === 'blocks' || $objectType === 'commercial-blocks' || in_array('show_type', $paramNames)) {
            $params['show_type'] = 'list';
        }

        // Большинство API TrendAgent требуют MongoDB ObjectId для параметра city
        // Используем external_id если есть, иначе guid (для совместимости)
        // Исключение: некоторые старые API могут работать с guid
        if (!empty($city->external_id)) {
            // Все новые API требуют ObjectId (external_id)
            $params['city'] = $city->external_id;
        } else {
            // Fallback на guid, если external_id не заполнен (должно логироваться)
            Log::warning("ParseTrendData: City {$city->name} (guid: {$city->guid}) does not have external_id, using guid", [
                'city_id' => $city->id,
                'city_guid' => $city->guid,
                'object_type' => $objectType,
            ]);
            $params['city'] = $city->guid;
        }

        if (in_array('offset', $paramNames)) {
            $params['offset'] = $offset;
        }

        if (in_array('sort', $paramNames)) {
            // Для commercial-blocks API требует другие значения sort
            if ($objectType === 'commercial-blocks') {
                $params['sort'] = 'price'; // price, price_m2, d
            } else {
                $params['sort'] = 'id';
            }
            $params['sort_order'] = 'desc';
        }

        return $params;
    }

    /**
     * Вывод итоговой статистики
     */
    protected function displayStats(): void
    {
        $this->newLine();
        $this->info('📊 Итоговая статистика:');
        $this->newLine();

        $headers = ['Тип объекта', 'Всего получено', 'Создано', 'Обновлено', 'Ошибок'];
        $rows = [];

        foreach ($this->stats as $type => $stat) {
            $typeName = $this->objectTypes[$type]['name'] ?? $type;
            $rows[] = [
                $typeName,
                $stat['total'],
                $stat['created'],
                $stat['updated'],
                $stat['errors'],
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }
    
    /**
     * Получить статистику последнего запуска
     */
    public function getLastRunStats(): ?array
    {
        return $this->stats;
    }
    
    /**
     * Форматирование времени выполнения
     */
    protected function formatExecutionTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = round($seconds % 60, 2);
        
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ч';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' мин';
        }
        if ($secs > 0 || empty($parts)) {
            $parts[] = $secs . ' сек';
        }
        
        return implode(' ', $parts);
    }
}
