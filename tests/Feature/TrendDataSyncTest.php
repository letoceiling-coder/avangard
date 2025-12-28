<?php

namespace Tests\Feature;

use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Services\TrendDataSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendDataSyncTest extends TestCase
{
    use RefreshDatabase;

    protected TrendDataSyncService $syncService;
    protected City $city;
    protected Builder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncService = new TrendDataSyncService();
        
        $this->city = City::factory()->create([
            'guid' => 'msk',
            'name' => 'Москва',
        ]);

        $this->builder = Builder::factory()->create([
            'guid' => 'test-builder',
            'name' => 'Тестовый застройщик',
        ]);
    }

    /**
     * Тест: Синхронизация нового блока из API
     */
    public function test_sync_new_block_from_api(): void
    {
        $apiData = [
            '_id' => '5ab8d3187be62f4b7f09eb9e',
            'guid' => 'test-block',
            'name' => 'ЖК Тестовый',
            'address' => ['Москва, ул. Тестовая, 1'],
            'city' => [
                '_id' => $this->city->external_id,
                'guid' => $this->city->guid,
                'name' => $this->city->name,
            ],
            'builder' => [
                '_id' => $this->builder->external_id,
                'guid' => $this->builder->guid,
                'name' => $this->builder->name,
            ],
            'latitude' => 55.7558,
            'longitude' => 37.6173,
            'status' => 1,
            'is_suite' => false,
            'exclusive' => false,
            'min_price' => 5000000,
            'max_price' => 15000000,
            'apartmentsCount' => 100,
            'viewApartmentsCount' => 50,
            'exclusiveApartmentsCount' => 10,
            'deadline' => '2025 Q4',
            'data_source' => 'parser',
        ];

        $block = $this->syncService->syncBlock($apiData, [
            'create_missing_references' => true,
            'update_existing' => true,
            'log_errors' => true,
            'skip_errors' => false,
        ]);

        $this->assertInstanceOf(Block::class, $block);
        $this->assertEquals('test-block', $block->guid);
        $this->assertEquals('parser', $block->data_source);
        $this->assertNotNull($block->parsed_at);
        $this->assertNotNull($block->last_synced_at);
    }

    /**
     * Тест: Обновление существующего блока
     */
    public function test_sync_updates_existing_block(): void
    {
        $existingBlock = Block::factory()->create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'external_id' => '5ab8d3187be62f4b7f09eb9e',
            'name' => 'Старое название',
            'apartments_count' => 50,
        ]);

        $apiData = [
            '_id' => '5ab8d3187be62f4b7f09eb9e',
            'guid' => $existingBlock->guid,
            'name' => 'Новое название',
            'city' => [
                'guid' => $this->city->guid,
                'name' => $this->city->name,
            ],
            'builder' => [
                'guid' => $this->builder->guid,
                'name' => $this->builder->name,
            ],
            'apartmentsCount' => 100,
            'status' => 1,
        ];

        $block = $this->syncService->syncBlock($apiData, [
            'update_existing' => true,
        ]);

        $this->assertEquals($existingBlock->id, $block->id);
        $this->assertEquals('Новое название', $block->name);
        $this->assertEquals(100, $block->apartments_count);
    }

    /**
     * Тест: Обработка ошибок при синхронизации
     */
    public function test_sync_handles_errors(): void
    {
        $apiData = [
            '_id' => 'invalid',
            'guid' => 'test',
            // Отсутствуют обязательные поля
        ];

        try {
            $this->syncService->syncBlock($apiData, [
                'skip_errors' => false,
                'log_errors' => true,
            ]);
            $this->fail('Должно было быть выброшено исключение');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\App\Exceptions\TrendParserException::class, $e);
        }

        // Проверяем что ошибка залогирована
        $this->assertDatabaseHas('parser_errors', [
            'object_type' => 'block',
            'external_id' => 'invalid',
        ]);
    }

    /**
     * Тест: Создание недостающих справочников
     */
    public function test_sync_creates_missing_references(): void
    {
        $apiData = [
            '_id' => 'test-block',
            'guid' => 'test-block',
            'name' => 'ЖК Тестовый',
            'city' => [
                '_id' => 'new-city-id',
                'guid' => 'new-city',
                'name' => 'Новый город',
            ],
            'builder' => [
                '_id' => 'new-builder-id',
                'guid' => 'new-builder',
                'name' => 'Новый застройщик',
            ],
            'status' => 1,
        ];

        $block = $this->syncService->syncBlock($apiData, [
            'create_missing_references' => true,
        ]);

        $this->assertDatabaseHas('cities', ['guid' => 'new-city']);
        $this->assertDatabaseHas('builders', ['guid' => 'new-builder']);
    }
}

