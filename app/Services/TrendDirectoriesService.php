<?php

namespace App\Services;

use App\Models\Trend\City;
use App\Models\Trend\Location;
use App\Models\Trend\Region;
use App\Models\Trend\Subway;
use App\Models\Trend\SubwayLine;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы со справочниками TrendAgent API
 * 
 * Получает и синхронизирует справочные данные (регионы, локации, метро и т.д.)
 * из endpoint https://apartment-api.trendagent.ru/v1/directories
 */
class TrendDirectoriesService
{
    private Client $httpClient;
    private string $baseUrl = 'https://apartment-api.trendagent.ru/v1';
    private string $authToken;

    /**
     * Доступные типы справочников для синхронизации
     */
    private array $syncableTypes = [
        'regions',
        'locations',
        'subways',
    ];

    public function __construct(?string $authToken = null)
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false,
        ]);
        
        $this->authToken = $authToken ?? '';
    }

    /**
     * Установить токен авторизации
     */
    public function setAuthToken(string $authToken): self
    {
        $this->authToken = $authToken;
        return $this;
    }

    /**
     * Получить справочники из API
     * 
     * @param City $city Город для которого получить справочники
     * @param array $types Типы справочников для получения (по умолчанию все syncable)
     * @return array Данные справочников
     * @throws \Exception
     */
    public function fetchDirectories(City $city, array $types = []): array
    {
        if (empty($this->authToken)) {
            throw new \Exception('Токен авторизации не установлен. Используйте setAuthToken()');
        }

        if (empty($city->external_id)) {
            throw new \Exception("Для города '{$city->name}' (guid: {$city->guid}) не установлен external_id (ObjectId)");
        }

        if (empty($types)) {
            $types = $this->syncableTypes;
        }

        $endpoint = $this->baseUrl . '/directories';
        
        // Формируем параметры types для Guzzle (множественные параметры)
        $queryParams = [
            'auth_token' => $this->authToken,
            'city' => $city->external_id,
            'lang' => 'ru',
        ];
        
        // Добавляем types как массив (Guzzle автоматически создаст ?types=regions&types=locations&...)
        foreach ($types as $type) {
            $queryParams['types'][] = $type;
        }

        try {
            $response = $this->httpClient->get($endpoint, [
                'query' => $queryParams,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception("API вернул статус {$response->getStatusCode()}");
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON: ' . json_last_error_msg());
            }

            return $data;

        } catch (GuzzleException $e) {
            $response = method_exists($e, 'hasResponse') && $e->hasResponse() ? $e->getResponse() : null;
            $responseBody = $response ? $response->getBody()->getContents() : 'N/A';
            $statusCode = $response ? $response->getStatusCode() : 'N/A';
            
            Log::error('TrendDirectoriesService: Ошибка при получении справочников', [
                'city_id' => $city->id,
                'city_guid' => $city->guid,
                'city_external_id' => $city->external_id,
                'types' => $types,
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'response_body' => substr($responseBody, 0, 500),
            ]);
            
            throw new \Exception('Ошибка при получении справочников: ' . $e->getMessage() . 
                (($statusCode !== 'N/A') ? " (HTTP {$statusCode})" : ''));
        }
    }

    /**
     * Синхронизировать регионы для города
     */
    public function syncRegions(City $city, array $regionsData): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($regionsData as $regionData) {
            try {
                $externalId = $regionData['_id'] ?? null;
                $guid = $regionData['guid'] ?? null;
                $name = $regionData['name'] ?? null;

                if (empty($externalId) || empty($guid) || empty($name)) {
                    Log::warning('TrendDirectoriesService: Пропущен регион с неполными данными', [
                        'city_id' => $city->id,
                        'region_data' => $regionData,
                    ]);
                    $stats['skipped']++;
                    continue;
                }

                // Ищем регион по external_id или guid + city_id
                $region = Region::where(function ($query) use ($externalId, $guid, $city) {
                    $query->where('external_id', $externalId)
                          ->orWhere(function ($q) use ($guid, $city) {
                              $q->where('guid', $guid)
                                ->where('city_id', $city->id);
                          });
                })->first();

                $data = [
                    'city_id' => $city->id,
                    'guid' => $guid,
                    'name' => $name,
                    'external_id' => $externalId,
                    'crm_id' => $regionData['crm_id'] ?? null,
                    'is_active' => true,
                    'sort_order' => $regionData['priority'] ?? 500,
                ];

                if ($region) {
                    // Обновляем существующий регион
                    $region->update($data);
                    $stats['updated']++;
                } else {
                    // Создаем новый регион
                    Region::create($data);
                    $stats['created']++;
                }

            } catch (\Exception $e) {
                Log::error('TrendDirectoriesService: Ошибка при синхронизации региона', [
                    'city_id' => $city->id,
                    'region_data' => $regionData,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Синхронизировать локации для города
     */
    public function syncLocations(City $city, array $locationsData): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($locationsData as $locationData) {
            try {
                $externalId = $locationData['_id'] ?? null;
                $guid = $locationData['guid'] ?? null;
                $name = $locationData['name'] ?? null;

                if (empty($externalId) || empty($guid) || empty($name)) {
                    Log::warning('TrendDirectoriesService: Пропущена локация с неполными данными', [
                        'city_id' => $city->id,
                        'location_data' => $locationData,
                    ]);
                    $stats['skipped']++;
                    continue;
                }

                // Ищем локацию по external_id или guid + city_id
                $location = Location::where(function ($query) use ($externalId, $guid, $city) {
                    $query->where('external_id', $externalId)
                          ->orWhere(function ($q) use ($guid, $city) {
                              $q->where('guid', $guid)
                                ->where('city_id', $city->id);
                          });
                })->first();

                $data = [
                    'city_id' => $city->id,
                    'guid' => $guid,
                    'name' => $name,
                    'external_id' => $externalId,
                    'crm_id' => $locationData['crm_id'] ?? null,
                    'is_active' => true,
                    'sort_order' => 0,
                ];

                if ($location) {
                    // Обновляем существующую локацию
                    $location->update($data);
                    $stats['updated']++;
                } else {
                    // Создаем новую локацию
                    Location::create($data);
                    $stats['created']++;
                }

            } catch (\Exception $e) {
                Log::error('TrendDirectoriesService: Ошибка при синхронизации локации', [
                    'city_id' => $city->id,
                    'location_data' => $locationData,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Синхронизировать метро для города
     * 
     * @note Метро требует также синхронизации линий метро (SubwayLine)
     */
    public function syncSubways(City $city, array $subwaysData): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // Сначала создаем/обновляем линии метро
        $linesMap = [];
        foreach ($subwaysData as $subwayData) {
            $lineNumber = $subwayData['line_number'] ?? null;
            $lineColor = $subwayData['color'] ?? null;

            if ($lineNumber && !isset($linesMap[$lineNumber])) {
                $line = SubwayLine::firstOrCreate(
                    [
                        'city_id' => $city->id,
                        'line_number' => $lineNumber,
                    ],
                    [
                        'name' => "Линия {$lineNumber}",
                        'color' => $lineColor ?? '#000000',
                        'is_active' => true,
                    ]
                );
                $linesMap[$lineNumber] = $line->id;
            }
        }

        // Теперь синхронизируем станции метро
        foreach ($subwaysData as $subwayData) {
            try {
                $externalId = $subwayData['_id'] ?? null;
                $guid = $subwayData['guid'] ?? null;
                $name = $subwayData['name'] ?? null;
                $lineNumber = $subwayData['line_number'] ?? null;

                if (empty($externalId) || empty($guid) || empty($name) || empty($lineNumber)) {
                    Log::warning('TrendDirectoriesService: Пропущена станция метро с неполными данными', [
                        'city_id' => $city->id,
                        'subway_data' => $subwayData,
                    ]);
                    $stats['skipped']++;
                    continue;
                }

                // Ищем станцию по external_id или guid + city_id
                $subway = Subway::where(function ($query) use ($externalId, $guid, $city) {
                    $query->where('external_id', $externalId)
                          ->orWhere(function ($q) use ($guid, $city) {
                              $q->where('guid', $guid)
                                ->where('city_id', $city->id);
                          });
                })->first();

                $data = [
                    'city_id' => $city->id,
                    'subway_line_id' => $linesMap[$lineNumber] ?? null,
                    'guid' => $guid,
                    'name' => $name,
                    'external_id' => $externalId,
                    'crm_id' => $subwayData['crm_id'] ?? null,
                    'priority' => $subwayData['priority'] ?? 500,
                    'is_active' => true,
                    'sort_order' => 0,
                ];

                if ($subway) {
                    // Обновляем существующую станцию
                    $subway->update($data);
                    $stats['updated']++;
                } else {
                    // Создаем новую станцию
                    Subway::create($data);
                    $stats['created']++;
                }

            } catch (\Exception $e) {
                Log::error('TrendDirectoriesService: Ошибка при синхронизации станции метро', [
                    'city_id' => $city->id,
                    'subway_data' => $subwayData,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Полная синхронизация всех справочников для города
     */
    public function syncAll(City $city): array
    {
        $result = [
            'success' => false,
            'city' => $city->name,
            'guid' => $city->guid,
            'stats' => [],
            'errors' => [],
        ];

        try {
            // Получаем справочники из API
            $directories = $this->fetchDirectories($city, $this->syncableTypes);

            $totalStats = [
                'regions' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
                'locations' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
                'subways' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            ];

            // Синхронизируем регионы
            if (isset($directories['regions']) && is_array($directories['regions'])) {
                $totalStats['regions'] = $this->syncRegions($city, $directories['regions']);
            }

            // Синхронизируем локации
            if (isset($directories['locations']) && is_array($directories['locations'])) {
                $totalStats['locations'] = $this->syncLocations($city, $directories['locations']);
            }

            // Синхронизируем метро
            if (isset($directories['subways']) && is_array($directories['subways'])) {
                $totalStats['subways'] = $this->syncSubways($city, $directories['subways']);
            }

            $result['stats'] = $totalStats;
            $result['success'] = true;

            Log::info('TrendDirectoriesService: Успешная синхронизация справочников', [
                'city_id' => $city->id,
                'city_guid' => $city->guid,
                'stats' => $totalStats,
            ]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            
            Log::error('TrendDirectoriesService: Ошибка при синхронизации справочников', [
                'city_id' => $city->id,
                'city_guid' => $city->guid,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }
}

