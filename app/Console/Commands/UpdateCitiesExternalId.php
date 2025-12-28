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

        $query = City::where('is_active', true);

        if (!empty($cityGuids)) {
            $query->whereIn('guid', $cityGuids);
        }

        return $query->get();
    }

    /**
     * Получить external_id (MongoDB ObjectId) для города из API
     * 
     * Пытается получить ObjectId из ответа API blocks
     */
    protected function getCityExternalId(City $city): ?string
    {
        try {
            // Пробуем получить из API blocks (даже если city передается как guid,
            // в ответе может быть информация о городе с его _id)
            $endpoint = 'https://api.trendagent.ru/v4_29/blocks/search/';
            
            // Для blocks API нужно использовать external_id, но его у нас еще нет
            // Попробуем другой подход - используем endpoint, который может работать с guid
            // или попробуем получить из первого блока в ответе
            
            // Альтернативный подход: попробовать использовать guid и посмотреть в ответе
            // Если API вернет ошибку, но в некоторых случаях может вернуть данные о городе
            
            // Но самый надежный способ - попробовать получить через endpoint /cities или подобный
            // Пока используем подход: делаем запрос к blocks API и смотрим, есть ли в ответе информация о городе
            
            // Попробуем сделать запрос с минимальными параметрами
            $response = $this->httpClient->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'lang' => 'ru',
                    'count' => 1,
                    'show_type' => 'list',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                
                // Ищем информацию о городе в ответе
                if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
                    foreach ($data['data'] as $block) {
                        // Проверяем, есть ли информация о городе в блоке
                        if (isset($block['city'])) {
                            $cityData = $block['city'];
                            
                            // Проверяем, совпадает ли guid города
                            if (isset($cityData['guid']) && $cityData['guid'] === $city->guid) {
                                // Возвращаем _id города, если он есть
                                if (isset($cityData['_id'])) {
                                    return $cityData['_id'];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Если блоки не работают, пробуем другой endpoint
            Log::debug('UpdateCitiesExternalId: Blocks API approach failed', [
                'city_guid' => $city->guid,
                'error' => $e->getMessage(),
            ]);
        }

        // Альтернативный подход: попробовать через endpoint списка городов (если есть)
        // Или использовать другие способы получения ObjectId
        
        return null;
    }
}

