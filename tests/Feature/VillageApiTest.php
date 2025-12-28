<?php

namespace Tests\Feature;

use App\Models\Trend\Village;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageApiTest extends TestCase
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

        // Создаем справочники (используем firstOrCreate для избежания конфликтов)
        $this->city = City::firstOrCreate(
            ['guid' => 'msk'],
            [
                'name' => 'Москва',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $this->builder = Builder::firstOrCreate(
            ['guid' => 'test-builder-' . $this->user->id],
            [
                'name' => 'Тестовый застройщик',
                'is_active' => true,
            ]
        );
    }

    /**
     * Тест: Получение списка поселков
     */
    public function test_get_villages_list(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Village::create([
                'city_id' => $this->city->id,
                'builder_id' => $this->builder->id,
                'guid' => "test-village-{$i}-" . uniqid(),
                'name' => "Тестовый поселок {$i}",
                'is_active' => true,
                'data_source' => 'manual',
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/villages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'guid',
                        'name',
                        'city',
                        'is_active',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Тест: Создание поселка
     */
    public function test_create_village(): void
    {
        $guid = 'test-village-1-' . uniqid();
        $data = [
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => $guid,
            'name' => 'Тестовый поселок',
            'address' => 'Тестовый адрес',
            'is_active' => true,
            'data_source' => 'manual',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/villages', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'guid',
                    'name',
                    'city',
                    'builder',
                    'is_active',
                ],
            ]);

        $this->assertDatabaseHas('villages', [
            'guid' => $guid,
            'name' => 'Тестовый поселок',
        ]);
    }

    /**
     * Тест: Получение поселка по ID
     */
    public function test_get_village_by_id(): void
    {
        $village =         Village::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-village-1',
            'name' => 'Тестовый поселок',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/villages/{$village->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'guid',
                    'name',
                    'city',
                    'builder',
                    'is_active',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $village->id,
                ],
            ]);
    }

    /**
     * Тест: Обновление поселка
     */
    public function test_update_village(): void
    {
        $village = Village::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-village-update-' . uniqid(),
            'name' => 'Старое название',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/villages/{$village->id}", [
            'name' => 'Новое название',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('villages', [
            'id' => $village->id,
            'name' => 'Новое название',
        ]);
    }

    /**
     * Тест: Удаление поселка
     */
    public function test_delete_village(): void
    {
        $village = Village::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-village-delete-' . uniqid(),
            'name' => 'Поселок для удаления',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/villages/{$village->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('villages', [
            'id' => $village->id,
        ]);
    }

    /**
     * Тест: Фильтрация по городу
     */
    public function test_filter_villages_by_city(): void
    {
        $city2 = City::factory()->create(['guid' => 'spb', 'name' => 'СПб']);

        for ($i = 0; $i < 3; $i++) {
            Village::create([
                'city_id' => $this->city->id,
                'guid' => "test-village-city1-{$i}-" . uniqid(),
                'name' => "Тестовый поселок 1-{$i}",
                'is_active' => true,
                'data_source' => 'manual',
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Village::create([
                'city_id' => $city2->id,
                'guid' => "test-village-city2-{$i}-" . uniqid(),
                'name' => "Тестовый поселок 2-{$i}",
                'is_active' => true,
                'data_source' => 'manual',
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/villages?city_id={$this->city->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /**
     * Тест: Получение устаревших поселков
     */
    public function test_get_outdated_villages(): void
    {
        Village::create([
            'city_id' => $this->city->id,
            'guid' => 'test-village-outdated-' . uniqid(),
            'name' => 'Устаревший поселок',
            'last_synced_at' => now()->subDays(10),
            'is_active' => true,
            'data_source' => 'parser',
        ]);

        Village::create([
            'city_id' => $this->city->id,
            'guid' => 'test-village-recent-' . uniqid(),
            'name' => 'Свежий поселок',
            'last_synced_at' => now()->subDays(3),
            'is_active' => true,
            'data_source' => 'parser',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/villages/outdated?days=7');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }
}

