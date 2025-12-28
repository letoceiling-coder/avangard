<?php

namespace App\Console\Commands;

use App\Models\Trend\City;
use App\Services\TrendSsoApiAuth;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Команда для обновления external_id (MongoDB ObjectId) для городов
 * 
 * Получает ObjectId из ответа API blocks и обновляет города в БД
 */
class UpdateCitiesExternalId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cities:update-external-id 
                            {--phone=+79045393434 : Телефон для авторизации}
                            {--password=nwBvh4q : Пароль для авторизации}
                            {--city=* : GUID конкретных городов (если не указано, обновляются все активные)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновить external_id (MongoDB ObjectId) для городов из API TrendAgent';

    protected TrendSsoApiAuth $auth;
    protected Client $httpClient;
    protected ?string $authToken = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Обновление external_id для городов...');
        $this->newLine();

        // Инициализация
        $this->auth = new TrendSsoApiAuth();
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false,
        ]);

        // Авторизация
        if (!$this->authenticate()) {
            return 1;
        }

        // Получение списка городов для обновления
        $cities = $this->getCities();
        if ($cities->isEmpty()) {
            $this->error('❌ Не найдено городов для обновления');
            return 1;
        }

        $this->info("✅ Найдено городов: {$cities->count()}");
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($cities->count());
        $bar->start();

        foreach ($cities as $city) {
            try {
                // Пропускаем, если уже есть external_id
                if (!empty($city->external_id)) {
                    $bar->advance();
                    $skipped++;
                    continue;
                }

                // Получаем ObjectId из API
                $externalId = $this->getCityExternalId($city);

                if ($externalId) {
                    $city->update(['external_id' => $externalId]);
                    $updated++;
                    
                    $this->line("\n✅ {$city->name} (guid: {$city->guid}) → external_id: {$externalId}");
                } else {
                    $errors++;
                    $this->line("\n⚠️  {$city->name} (guid: {$city->guid}) → ObjectId не найден");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->line("\n❌ Ошибка для {$city->name}: " . $e->getMessage());
                Log::error('UpdateCitiesExternalId: Error updating city', [
                    'city_id' => $city->id,
                    'city_guid' => $city->guid,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
            
            // Небольшая пауза между запросами
            usleep(500000); // 0.5 секунды
        }

        $bar->finish();
        $this->newLine(2);

        // Итоговая статистика
        $this->info("📊 Итоговая статистика:");
        $this->table(
            ['Действие', 'Количество'],
            [
                ['Обновлено', $updated],
                ['Пропущено (уже есть external_id)', $skipped],
                ['Ошибок', $errors],
                ['Всего', $cities->count()],
            ]
        );

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
            Log::error('UpdateCitiesExternalId: Authentication failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Получить список городов для обновления
     */
    protected function getCities()
    {
        $cityGuids = $this->option('city');

        $query = City::where('is_active', true)
            ->whereNotNull('region_id'); // Только города (с region_id), не регионы

        if (!empty($cityGuids)) {
            $query->whereIn('guid', $cityGuids);
        }

        return $query->get();
    }

    /**
     * Получить external_id (MongoDB ObjectId) для города из API
     * 
     * Пытается получить ObjectId из ответов API других типов объектов,
     * которые работают с guid городов (parkings, villages, commercial-blocks)
     */
    protected function getCityExternalId(City $city): ?string
    {
        // Пробуем разные endpoints, которые работают с guid города
        $endpoints = [
            // Parkings API - работает с guid
            [
                'url' => 'https://parkings.trendagent.ru/search/places/',
                'params' => [
                    'city' => $city->guid,
                    'lang' => 'ru',
                    'count' => 10, // Берем больше, чтобы найти объекты с информацией о городе
                ],
            ],
            // Villages API - работает с guid
            [
                'url' => 'https://house-api.trendagent.ru/v1/search/villages',
                'params' => [
                    'city' => $city->guid,
                    'lang' => 'ru',
                    'count' => 10,
                ],
            ],
            // Commercial blocks API - работает с guid
            [
                'url' => 'https://commerce.trendagent.ru/search/blocks/',
                'params' => [
                    'city' => $city->guid,
                    'lang' => 'ru',
                    'count' => 10,
                    'show_type' => 'list',
                ],
            ],
        ];

        foreach ($endpoints as $endpointConfig) {
            try {
                $response = $this->httpClient->get($endpointConfig['url'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->authToken,
                        'Accept' => 'application/json',
                    ],
                    'query' => $endpointConfig['params'],
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody()->getContents(), true);
                    
                    // Ищем информацию о городе в ответе
                    $cityId = $this->extractCityIdFromResponse($data, $city->guid);
                    
                    if ($cityId) {
                        Log::info('UpdateCitiesExternalId: Found city ObjectId', [
                            'city_guid' => $city->guid,
                            'city_name' => $city->name,
                            'external_id' => $cityId,
                            'source_endpoint' => $endpointConfig['url'],
                        ]);
                        return $cityId;
                    }
                }
            } catch (\Exception $e) {
                // Продолжаем пробовать другие endpoints
                Log::debug('UpdateCitiesExternalId: Endpoint failed', [
                    'city_guid' => $city->guid,
                    'endpoint' => $endpointConfig['url'],
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Если не нашли в API, возвращаем null
        return null;
    }

    /**
     * Извлечь ObjectId города из ответа API
     */
    protected function extractCityIdFromResponse(array $data, string $cityGuid): ?string
    {
        // Проверяем разные структуры ответа
        $items = $data['data']['results'] ?? $data['data'] ?? $data['results'] ?? $data['items'] ?? [];
        
        if (!is_array($items)) {
            Log::debug('UpdateCitiesExternalId: No items found in response', [
                'city_guid' => $cityGuid,
                'data_keys' => array_keys($data),
            ]);
            return null;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Проверяем поле city в разных форматах
            $cityData = $item['city'] ?? $item['City'] ?? null;
            
            if (is_array($cityData)) {
                // Проверяем, совпадает ли guid
                $itemCityGuid = $cityData['guid'] ?? $cityData['GUID'] ?? null;
                
                if ($itemCityGuid === $cityGuid) {
                    // Возвращаем _id города
                    $cityId = $cityData['_id'] ?? $cityData['id'] ?? null;
                    if ($cityId) {
                        $cityId = (string) $cityId;
                        // MongoDB ObjectId должен быть 24 символа (hex)
                        if (strlen($cityId) === 24 && ctype_xdigit($cityId)) {
                            Log::debug('UpdateCitiesExternalId: Found city ObjectId', [
                                'city_guid' => $cityGuid,
                                'city_id' => $cityId,
                                'item_keys' => array_keys($item),
                            ]);
                            return $cityId;
                        }
                    }
                }
            }
        }

        // Логируем структуру первого элемента для отладки
        if (!empty($items[0])) {
            $firstItem = $items[0];
            Log::debug('UpdateCitiesExternalId: Response structure', [
                'city_guid' => $cityGuid,
                'first_item_keys' => array_keys($firstItem),
                'has_city' => isset($firstItem['city']),
                'city_structure' => isset($firstItem['city']) && is_array($firstItem['city']) 
                    ? array_keys($firstItem['city']) 
                    : gettype($firstItem['city'] ?? null),
            ]);
        }

        return null;
    }
}

