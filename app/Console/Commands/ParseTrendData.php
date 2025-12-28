<?php

namespace App\Console\Commands;

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
                            {--phone=+79045393434 : Телефон для авторизации}
                            {--password=nwBvh4q : Пароль для авторизации}';

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
            'params' => ['city', 'lang', 'count', 'offset', 'sort', 'sort_order'],
        ],
        'parkings' => [
            'name' => 'Паркинги',
            'endpoint' => 'https://parkings.trendagent.ru/search/places/',
            'method' => 'syncParking',
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
            'endpoint' => 'https://house-api.trendagent.ru/v1/filter/plots',
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
        if ($cities->isEmpty()) {
            $executionTime = microtime(true) - $startTime;
            $this->error('❌ Не найдено активных городов для парсинга');
            Log::warning('ParseTrendData: No active cities found', [
                'execution_time_seconds' => round($executionTime, 2),
            ]);
            return 1;
        }

        $this->info("✅ Найдено городов: {$cities->count()}");
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
        $phone = $this->option('phone');
        $password = $this->option('password');

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

        if (!empty($cityGuids)) {
            return City::whereIn('guid', $cityGuids)
                ->where('is_active', true)
                ->get();
        }

        return City::where('is_active', true)->get();
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
        $params = $this->buildParams($typeConfig['params'], $city, $limit, $offset);

        try {
            // Запрос к API
            $response = $this->httpClient->get($endpoint, [
                'query' => $params,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['data']) || !is_array($data['data'])) {
                Log::warning("ParseTrendData: Invalid response structure for {$type}", [
                    'city_guid' => $city->guid,
                    'response_keys' => array_keys($data),
                ]);
                return;
            }

            $objects = $data['data'];
            $totalFound = count($objects);

            $this->stats[$type]['total'] += $totalFound;

            // Синхронизация каждого объекта
            foreach ($objects as $objectData) {
                try {
                    $syncMethod = $typeConfig['method'];
                    
                    if (!method_exists($this->syncService, $syncMethod)) {
                        throw new \Exception("Метод {$syncMethod} не существует в TrendDataSyncService");
                    }
                    
                    // Вызываем соответствующий метод синхронизации
                    $syncedObject = $this->syncService->$syncMethod($objectData, $options);

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

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error("ParseTrendData: API request failed", [
                'type' => $type,
                'city_guid' => $city->guid,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Построение параметров запроса
     */
    protected function buildParams(array $paramNames, City $city, int $limit, int $offset): array
    {
        $params = [
            'city' => $city->guid,
            'lang' => 'ru',
            'count' => $limit,
        ];

        if (in_array('offset', $paramNames)) {
            $params['offset'] = $offset;
        }

        if (in_array('sort', $paramNames)) {
            $params['sort'] = 'id';
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
