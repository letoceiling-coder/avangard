<?php

namespace App\Console\Commands;

use App\Services\TrendSsoApiAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class TestTrendApiEndpoints extends Command
{
    protected $signature = 'trend:test-endpoints 
                            {--phone=+79045393434 : Телефон для авторизации}
                            {--password=nwBvh4q : Пароль для авторизации}
                            {--city=5a5cb42159042faa9a218d04 : ID города}';

    protected $description = 'Тестирование всех TrendAgent API endpoints для анализа структуры данных';

    private TrendSsoApiAuth $auth;
    private Client $client;
    private string $authToken;
    private string $city;

    public function handle()
    {
        $phone = $this->option('phone');
        $password = $this->option('password');
        $this->city = $this->option('city');

        $this->info("🔐 Авторизация через Trend SSO...");
        
        try {
            $this->auth = new TrendSsoApiAuth();
            $authData = $this->auth->authenticate($phone, $password);
            
            if (!($authData['authenticated'] ?? false)) {
                $this->error("❌ Авторизация не удалась");
                return 1;
            }

            $this->authToken = $this->auth->getAuthToken();
            
            if (empty($this->authToken)) {
                $this->error("❌ Токен авторизации не найден");
                return 1;
            }

            $this->info("✅ Авторизация успешна!");
            
            $this->client = new Client([
                'timeout' => 30,
                'verify' => false,
            ]);

            // Определяем все endpoints для тестирования
            $endpoints = $this->getEndpointsToTest();
            
            $resultsDir = storage_path('app/trend_api_responses');
            if (!is_dir($resultsDir)) {
                mkdir($resultsDir, 0755, true);
            }

            $this->info("\n📊 Начинаем тестирование " . count($endpoints) . " endpoints...\n");

            $successCount = 0;
            $errorCount = 0;
            $results = [];

            foreach ($endpoints as $index => $endpoint) {
                $num = $index + 1;
                $total = count($endpoints);
                
                $this->info("[{$num}/{$total}] Тестируем: {$endpoint['name']}");
                $this->line("   URL: {$endpoint['url']}");

                try {
                    $response = $this->makeRequest($endpoint);
                    $results[] = [
                        'endpoint' => $endpoint['name'],
                        'url' => $endpoint['url'],
                        'status' => 'success',
                        'response' => $response,
                    ];
                    
                    // Сохраняем результат в файл
                    $filename = $this->sanitizeFilename($endpoint['name']) . '.json';
                    file_put_contents(
                        $resultsDir . '/' . $filename,
                        json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    );
                    
                    $successCount++;
                    $this->info("   ✅ Успешно");
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'endpoint' => $endpoint['name'],
                        'url' => $endpoint['url'],
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                    
                    $errorCount++;
                    $this->error("   ❌ Ошибка: " . $e->getMessage());
                }
                
                // Небольшая задержка между запросами
                usleep(500000); // 0.5 секунды
            }

            // Сохраняем сводку
            $summary = [
                'total' => count($endpoints),
                'success' => $successCount,
                'errors' => $errorCount,
                'tested_at' => now()->toIso8601String(),
                'results' => $results,
            ];
            
            file_put_contents(
                $resultsDir . '/summary.json',
                json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $this->info("\n📈 Итоги тестирования:");
            $this->info("   Всего: {$summary['total']}");
            $this->info("   ✅ Успешно: {$successCount}");
            $this->info("   ❌ Ошибок: {$errorCount}");
            $this->info("\n💾 Результаты сохранены в: {$resultsDir}/");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Критическая ошибка: " . $e->getMessage());
            Log::error('Trend API endpoints test error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    private function makeRequest(array $endpoint): array
    {
        $url = $endpoint['url'];
        $method = $endpoint['method'] ?? 'GET';

        $response = $this->client->request($method, $url, [
            'headers' => [
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Не удалось декодировать JSON: ' . json_last_error_msg());
        }

        return [
            'status_code' => $statusCode,
            'data' => $data,
            'raw_body_length' => strlen($body),
        ];
    }

    private function getEndpointsToTest(): array
    {
        $token = $this->authToken;
        $city = $this->city;
        
        return [
            // Квартиры - Блоки
            [
                'name' => 'blocks_search_list',
                'url' => "https://api.trendagent.ru/v4_29/blocks/search/?show_type=list&sort=price&sort_order=asc&count=5&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'blocks_search_map',
                'url' => "https://api.trendagent.ru/v4_29/blocks/search/?show_type=map&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'blocks_search_houses',
                'url' => "https://api.trendagent.ru/v4_29/blocks/search/?show_type=list&count=5&sort=start_sale_to&sort_order=desc&room=30&room=40&city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Квартиры - Прямой поиск
            [
                'name' => 'apartments_search',
                'url' => "https://api.trendagent.ru/v4_29/apartments/search/?sort=price&sort_order=asc&count=5&city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Прелаунчи
            [
                'name' => 'prelaunches_search',
                'url' => "https://api.trendagent.ru/v4_29/prelaunches/search?sort=price&sort_order=asc&city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Эксклюзивы
            [
                'name' => 'exclusives',
                'url' => "https://api.trendagent.ru/v4_29/exclusives?city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Справочники квартир
            [
                'name' => 'directories_rooms',
                'url' => "https://api.trendagent.ru/v4_29/directories/rooms/?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'unit_measurements',
                'url' => "https://api.trendagent.ru/v4_29/unit_measurements?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'tariffs',
                'url' => "https://api.trendagent.ru/v4_29/tariffs/?city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Паркинг
            [
                'name' => 'parkings_search_places',
                'url' => "https://parkings.trendagent.ru/search/places/?count=5&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'parkings_enums_contract_types',
                'url' => "https://parkings.trendagent.ru/enums/contract_types?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'parkings_enums_parking_types',
                'url' => "https://parkings.trendagent.ru/enums/parking_types?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'parkings_enums_payment_types',
                'url' => "https://parkings.trendagent.ru/enums/payment_types?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'parkings_enums_place_types',
                'url' => "https://parkings.trendagent.ru/enums/place_types?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'parkings_directories_deadlines',
                'url' => "https://parkings.trendagent.ru/directories/deadlines/?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'parkings_directories_sales_start',
                'url' => "https://parkings.trendagent.ru/directories/sales_start/?city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Дома и участки
            [
                'name' => 'houses_search_villages',
                'url' => "https://house-api.trendagent.ru/v1/search/villages?count=5&sort_type=start_sale_to&sort_order=desc&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'houses_filter_plots',
                'url' => "https://house-api.trendagent.ru/v1/filter/plots?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'houses_filter_railway_stations',
                'url' => "https://house-api.trendagent.ru/v1/filter/railway-stations?limit=20&isJSON=true&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'houses_filter_escrow_banks',
                'url' => "https://house-api.trendagent.ru/v1/filter/escrow-banks?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'houses_filter',
                'url' => "https://house-api.trendagent.ru/v1/filter?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'houses_builders_exclusives',
                'url' => "https://house-api.trendagent.ru/v1/builders/exclusives?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'houses_projects_search',
                'url' => "https://house-api.trendagent.ru/v1/projects/search/?count=5&city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Коммерция
            [
                'name' => 'commercial_search_blocks',
                'url' => "https://commerce.trendagent.ru/search/blocks/?show_type=list&count=5&sort=sales_start&sort_order=desc&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'commercial_search_premises',
                'url' => "https://commerce.trendagent.ru/search/premises?count=5&city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'commercial_filters',
                'url' => "https://commerce.trendagent.ru/filters?name=property_types&name=building_types&name=finishing_types&name=payment_types&name=banks&city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Дополнительные справочники для квартир
            [
                'name' => 'apartment_directories',
                'url' => "https://apartment-api.trendagent.ru/v1/directories?types=banks&types=subways&types=regions&city={$city}&lang=ru&auth_token={$token}",
            ],
            
            // Вебинары
            [
                'name' => 'webinars_types',
                'url' => "https://webinars-api.trendagent.ru/v1/webinar_types?city={$city}&lang=ru&auth_token={$token}",
            ],
            [
                'name' => 'webinars_events',
                'url' => "https://webinars-api.trendagent.ru/v1/events?city={$city}&date_from=" . date('Y-m-d\T00:00:00') . "&date_to=" . date('Y-m-d\T23:59:59') . "&lang=ru&auth_token={$token}",
            ],
        ];
    }

    private function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }
}

