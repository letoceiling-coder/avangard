<?php

namespace Tests\Feature;

use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\Trend\Subway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected City $city;
    protected Builder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем пользователя с ролью admin и получаем токен
        $this->user = User::factory()->create();
        
        // Назначаем роль admin
        $adminRole = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'slug' => 'admin']
        );
        $this->user->roles()->sync([$adminRole->id]);
        $this->user->refresh();
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Создаем справочники
        $this->city = City::factory()->create([
            'guid' => 'msk',
            'name' => 'Москва',
            'is_active' => true,
        ]);

        $this->builder = Builder::factory()->create([
            'guid' => 'test-builder',
            'name' => 'Тестовый застройщик',
            'is_active' => true,
        ]);
    }

    /**
     * Тест: Получение списка блоков (без фильтров)
     */
    public function test_get_blocks_list_without_filters(): void
    {
        // Создаем тестовые блоки
        Block::factory()->count(5)->create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'guid',
                        'name',
                        'city',
                        'builder',
                        'is_active',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Тест: Получение списка блоков с фильтром по городу
     */
    public function test_get_blocks_list_filtered_by_city(): void
    {
        $city2 = City::factory()->create(['guid' => 'spb', 'name' => 'Санкт-Петербург']);

        Block::factory()->count(3)->create([
            'city_id' => $this->city->id,
            'is_active' => true,
        ]);

        Block::factory()->count(2)->create([
            'city_id' => $city2->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/blocks?city_id={$this->city->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertEquals($this->city->id, $data[0]['city']['id']);
    }

    /**
     * Тест: Получение списка блоков с фильтром по застройщику
     */
    public function test_get_blocks_list_filtered_by_builder(): void
    {
        $builder2 = Builder::factory()->create(['guid' => 'builder2']);

        Block::factory()->count(3)->create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'is_active' => true,
        ]);

        Block::factory()->count(2)->create([
            'city_id' => $this->city->id,
            'builder_id' => $builder2->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/blocks?builder_id={$this->builder->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertEquals($this->builder->id, $data[0]['builder']['id']);
    }

    /**
     * Тест: Получение списка блоков с фильтром по цене
     */
    public function test_get_blocks_list_filtered_by_price(): void
    {
        // Создаем блоки с разными ценами (в копейках)
        Block::factory()->create([
            'city_id' => $this->city->id,
            'min_price' => 5000000, // 50,000 руб
            'max_price' => 10000000, // 100,000 руб
            'is_active' => true,
        ]);

        Block::factory()->create([
            'city_id' => $this->city->id,
            'min_price' => 15000000, // 150,000 руб
            'max_price' => 20000000, // 200,000 руб
            'is_active' => true,
        ]);

        // Фильтр: минимальная цена 100,000 руб
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks?min_price=100000');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertGreaterThanOrEqual(10000000, $data[0]['prices']['min']);
    }

    /**
     * Тест: Получение списка блоков с фильтром по эксклюзивности
     */
    public function test_get_blocks_list_filtered_by_exclusive(): void
    {
        Block::factory()->count(2)->create([
            'city_id' => $this->city->id,
            'is_exclusive' => true,
            'is_active' => true,
        ]);

        Block::factory()->count(3)->create([
            'city_id' => $this->city->id,
            'is_exclusive' => false,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks?is_exclusive=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        foreach ($data as $block) {
            $this->assertTrue($block['is_exclusive']);
        }
    }

    /**
     * Тест: Получение списка блоков с поиском
     */
    public function test_get_blocks_list_with_search(): void
    {
        Block::factory()->create([
            'city_id' => $this->city->id,
            'name' => 'ЖК ОКО',
            'address' => 'Москва, ул. Тестовая',
            'is_active' => true,
        ]);

        Block::factory()->create([
            'city_id' => $this->city->id,
            'name' => 'ЖК Другое',
            'address' => 'Москва, ул. Другая',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks?search=ОКО');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertStringContainsString('ОКО', $data[0]['name']);
    }

    /**
     * Тест: Получение списка блоков с сортировкой
     */
    public function test_get_blocks_list_with_sorting(): void
    {
        Block::factory()->create([
            'city_id' => $this->city->id,
            'name' => 'Блок A',
            'min_price' => 10000000,
            'is_active' => true,
        ]);

        Block::factory()->create([
            'city_id' => $this->city->id,
            'name' => 'Блок B',
            'min_price' => 5000000,
            'is_active' => true,
        ]);

        // Сортировка по цене по возрастанию
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks?sort=price&sort_direction=asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertLessThanOrEqual($data[1]['prices']['min'], $data[0]['prices']['min']);
    }

    /**
     * Тест: Получение списка блоков с пагинацией
     */
    public function test_get_blocks_list_with_pagination(): void
    {
        Block::factory()->count(25)->create([
            'city_id' => $this->city->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $data = $response->json();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(10, $data['meta']['per_page']);
        $this->assertEquals(25, $data['meta']['total']);
    }

    /**
     * Тест: Создание блока
     */
    public function test_create_block(): void
    {
        $subway = Subway::factory()->create([
            'city_id' => $this->city->id,
            'guid' => 'test-subway',
        ]);

        $blockData = [
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-block-' . time(),
            'name' => 'Тестовый ЖК',
            'address' => 'Москва, ул. Тестовая, 1',
            'latitude' => 55.7558,
            'longitude' => 37.6173,
            'min_price' => 5000000, // В копейках
            'max_price' => 15000000,
            'is_active' => true,
            'data_source' => 'manual',
            'subway_ids' => [$subway->id],
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/blocks', $blockData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'guid',
                    'name',
                    'city',
                    'builder',
                    'subways',
                ],
            ]);

        $this->assertDatabaseHas('blocks', [
            'guid' => $blockData['guid'],
            'name' => $blockData['name'],
        ]);

        // Проверяем связь с метро
        $block = Block::where('guid', $blockData['guid'])->first();
        $this->assertTrue($block->subways->contains($subway->id));
    }

    /**
     * Тест: Создание блока с валидацией ошибок
     */
    public function test_create_block_validation_errors(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/blocks', [
            // Отсутствуют обязательные поля
            'name' => 'Тестовый ЖК',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['city_id', 'guid']);
    }

    /**
     * Тест: Получение одного блока
     */
    public function test_get_single_block(): void
    {
        $block = Block::factory()->create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/blocks/{$block->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'guid',
                    'name',
                    'city',
                    'builder',
                    'subways',
                    'prices',
                    'images',
                ],
            ]);

        $this->assertEquals($block->id, $response->json('data.id'));
    }

    /**
     * Тест: Получение несуществующего блока
     */
    public function test_get_nonexistent_block(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks/99999');

        $response->assertStatus(404);
    }

    /**
     * Тест: Обновление блока
     */
    public function test_update_block(): void
    {
        $block = Block::factory()->create([
            'city_id' => $this->city->id,
            'name' => 'Старое название',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/blocks/{$block->id}", [
            'name' => 'Новое название',
            'min_price' => 6000000,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Новое название', $response->json('data.name'));
        $this->assertEquals(6000000, $response->json('data.prices.min'));

        $this->assertDatabaseHas('blocks', [
            'id' => $block->id,
            'name' => 'Новое название',
        ]);
    }

    /**
     * Тест: Удаление блока (soft delete)
     */
    public function test_delete_block(): void
    {
        $block = Block::factory()->create([
            'city_id' => $this->city->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/blocks/{$block->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Блок успешно удален']);

        // Проверяем soft delete
        $this->assertSoftDeleted('blocks', ['id' => $block->id]);
    }

    /**
     * Тест: Доступ без авторизации
     */
    public function test_unauthorized_access(): void
    {
        $response = $this->getJson('/api/v1/blocks');

        $response->assertStatus(401);
    }

    /**
     * Тест: Получение списка с фильтром по метро
     */
    public function test_get_blocks_filtered_by_subway(): void
    {
        $subway = Subway::factory()->create([
            'city_id' => $this->city->id,
            'guid' => 'test-subway',
        ]);

        $block1 = Block::factory()->create([
            'city_id' => $this->city->id,
            'is_active' => true,
        ]);

        $block2 = Block::factory()->create([
            'city_id' => $this->city->id,
            'is_active' => true,
        ]);

        // Привязываем метро только к первому блоку
        $block1->subways()->attach($subway->id, [
            'distance_time' => 5,
            'distance_type_id' => 1,
            'distance_type' => 'пешком',
            'priority' => 500,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/blocks?subway_id={$subway->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertTrue(
            collect($data)->contains(function ($block) use ($block1) {
                return $block['id'] === $block1->id;
            })
        );
    }

    /**
     * Тест: Получение списка с фильтром по источнику данных
     */
    public function test_get_blocks_filtered_by_data_source(): void
    {
        Block::factory()->count(3)->create([
            'city_id' => $this->city->id,
            'data_source' => 'parser',
            'is_active' => true,
        ]);

        Block::factory()->count(2)->create([
            'city_id' => $this->city->id,
            'data_source' => 'manual',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/blocks?data_source=parser');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $block) {
            $this->assertEquals('parser', $block['data_source']);
        }
    }
}

