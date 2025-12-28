<?php

namespace Tests\Feature;

use App\Models\Trend\City;
use App\Models\Trend\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Реальные тесты API парсера TrendAgent
 * 
 * ВАЖНО: Эти тесты требуют:
 * - Подключения к интернету
 * - Валидных credentials для TrendAgent SSO
 * - Заполненного external_id для городов (MongoDB ObjectId)
 */
class ParserRealApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $phone;
    protected string $password;

    protected function setUp(): void
    {
        parent::setUp();

        // Получаем credentials из env или используем тестовые
        $this->phone = env('TREND_SSO_PHONE', '');
        $this->password = env('TREND_SSO_PASSWORD', '');

        if (empty($this->phone) || empty($this->password)) {
            $this->markTestSkipped('TREND_SSO_PHONE и TREND_SSO_PASSWORD не настроены в .env');
        }

        // Создаем тестовые регионы и города
        $this->seedRegions();
    }

    /**
     * Создать тестовые регионы и города
     */
    protected function seedRegions(): void
    {
        // Московская область
        $moscowRegion = Region::create([
            'name' => 'Московская область',
            'guid' => 'mo',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        City::create([
            'region_id' => $moscowRegion->id,
            'name' => 'Москва',
            'guid' => 'msk',
            'external_id' => env('TREND_CITY_MSK_ID', null), // MongoDB ObjectId для Москвы
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Ленинградская область
        $spbRegion = Region::create([
            'name' => 'Ленинградская область',
            'guid' => 'lo',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        City::create([
            'region_id' => $spbRegion->id,
            'name' => 'Санкт-Петербург',
            'guid' => 'spb',
            'external_id' => env('TREND_CITY_SPB_ID', null), // MongoDB ObjectId для СПб
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Тест 1: Парсинг blocks для Москвы (базовый тест)
     */
    public function test_parse_blocks_moscow_basic(): void
    {
        $city = City::where('guid', 'msk')->first();
        
        if (!$city) {
            $this->markTestSkipped('Город Москва не найден');
        }

        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 5,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 2: Парсинг blocks для Москвы с проверкой изображений
     */
    public function test_parse_blocks_moscow_with_images(): void
    {
        $city = City::where('guid', 'msk')->first();
        
        if (!$city) {
            $this->markTestSkipped('Город Москва не найден');
        }

        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 3,
            '--check-images' => true,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 3: Парсинг blocks для Москвы с принудительным обновлением
     */
    public function test_parse_blocks_moscow_force(): void
    {
        $city = City::where('guid', 'msk')->first();
        
        if (!$city) {
            $this->markTestSkipped('Город Москва не найден');
        }

        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 5,
            '--force' => true,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 4: Парсинг blocks для Москвы с пропуском ошибок
     */
    public function test_parse_blocks_moscow_skip_errors(): void
    {
        $city = City::where('guid', 'msk')->first();
        
        if (!$city) {
            $this->markTestSkipped('Город Москва не найден');
        }

        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 5,
            '--skip-errors' => true,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 5: Парсинг blocks для Санкт-Петербурга (проверка external_id)
     */
    public function test_parse_blocks_spb(): void
    {
        $city = City::where('guid', 'spb')->first();
        
        if (!$city || empty($city->external_id)) {
            $this->markTestSkipped('Город СПб не найден или external_id не заполнен');
        }

        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'spb',
            '--limit' => 5,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 6: Парсинг нескольких типов объектов
     */
    public function test_parse_multiple_types(): void
    {
        $this->artisan('trend:parse', [
            '--type' => ['blocks', 'parkings'],
            '--city' => 'msk',
            '--limit' => 3,
            '--skip-errors' => true,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 7: Парсинг commercial-blocks
     */
    public function test_parse_commercial_blocks(): void
    {
        $this->artisan('trend:parse', [
            '--type' => 'commercial-blocks',
            '--city' => 'msk',
            '--limit' => 5,
            '--skip-errors' => true,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 8: Парсинг с большим лимитом
     */
    public function test_parse_blocks_large_limit(): void
    {
        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 50,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 9: Парсинг с offset
     */
    public function test_parse_blocks_with_offset(): void
    {
        $this->artisan('trend:parse', [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 10,
            '--offset' => 10,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }

    /**
     * Тест 10: Парсинг всех типов объектов
     */
    public function test_parse_all_types(): void
    {
        $this->artisan('trend:parse', [
            '--city' => 'msk',
            '--limit' => 3,
            '--skip-errors' => true,
            '--phone' => $this->phone,
            '--password' => $this->password,
        ])
            ->assertExitCode(0);
    }
}

