<?php

namespace Tests\Feature;

use App\Models\Trend\Parking;
use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected City $city;
    protected Builder $builder;
    protected Block $block;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        // Назначаем роль admin
        $adminRole = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'slug' => 'admin']
        );
        $this->user->roles()->sync([$adminRole->id]);
        $this->user->refresh();
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->city = City::factory()->create([
            'guid' => 'msk',
            'name' => 'Москва',
        ]);

        $this->builder = Builder::factory()->create([
            'guid' => 'test-builder',
            'name' => 'Тестовый застройщик',
        ]);

        $this->block = Block::factory()->create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
        ]);
    }

    /**
     * Тест: Получение списка парковочных мест
     */
    public function test_get_parkings_list(): void
    {
        Parking::factory()->count(5)->create([
            'city_id' => $this->city->id,
            'status' => 'available',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/parkings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'block_id',
                        'block_name',
                        'price',
                        'status',
                        'city',
                    ],
                ],
            ]);
    }

    /**
     * Тест: Получение списка с фильтром по статусу
     */
    public function test_get_parkings_filtered_by_status(): void
    {
        Parking::factory()->count(3)->create([
            'city_id' => $this->city->id,
            'status' => 'available',
        ]);

        Parking::factory()->count(2)->create([
            'city_id' => $this->city->id,
            'status' => 'booked',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/parkings?status=available');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $parking) {
            $this->assertEquals('available', $parking['status']);
        }
    }

    /**
     * Тест: Получение списка с фильтром по блоку
     */
    public function test_get_parkings_filtered_by_block(): void
    {
        $block2 = Block::factory()->create(['city_id' => $this->city->id]);

        Parking::factory()->count(3)->create([
            'city_id' => $this->city->id,
            'block_id' => $this->block->id,
        ]);

        Parking::factory()->count(2)->create([
            'city_id' => $this->city->id,
            'block_id' => $block2->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/parkings?block_id={$this->block->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /**
     * Тест: Создание парковочного места
     */
    public function test_create_parking(): void
    {
        $parkingData = [
            'city_id' => $this->city->id,
            'block_id' => $this->block->id,
            'number' => 'A-123',
            'floor' => -1,
            'area' => 12.5,
            'price' => 2000000, // В копейках
            'status' => 'available',
            'parking_type' => 'Подземный',
            'data_source' => 'manual',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/parkings', $parkingData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'price',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('parkings', [
            'number' => $parkingData['number'],
            'block_id' => $this->block->id,
        ]);
    }

    /**
     * Тест: Получение одного парковочного места
     */
    public function test_get_single_parking(): void
    {
        $parking = Parking::factory()->create([
            'city_id' => $this->city->id,
            'block_id' => $this->block->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/parkings/{$parking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'block',
                    'city',
                    'price',
                    'status',
                ],
            ]);
    }

    /**
     * Тест: Обновление парковочного места
     */
    public function test_update_parking(): void
    {
        $parking = Parking::factory()->create([
            'city_id' => $this->city->id,
            'status' => 'available',
            'price' => 2000000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/parkings/{$parking->id}", [
            'status' => 'booked',
            'price' => 2500000,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('booked', $response->json('data.status'));
        $this->assertEquals(2500000, $response->json('data.price'));
    }

    /**
     * Тест: Удаление парковочного места
     */
    public function test_delete_parking(): void
    {
        $parking = Parking::factory()->create([
            'city_id' => $this->city->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/parkings/{$parking->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('parkings', ['id' => $parking->id]);
    }
}

