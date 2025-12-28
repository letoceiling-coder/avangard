<?php

namespace Tests\Feature;

use App\Models\Trend\CommercialBlock;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialBlockApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected City $city;
    protected Builder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $adminRole = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'slug' => 'admin']
        );
        $this->user->roles()->sync([$adminRole->id]);
        $this->user->refresh();
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        $this->city = City::firstOrCreate(
            ['guid' => 'msk'],
            [
                'name' => 'Москва',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $this->builder = Builder::firstOrCreate(
            ['guid' => 'test-builder-commercial-' . $this->user->id],
            [
                'name' => 'Тестовый застройщик',
                'is_active' => true,
            ]
        );
    }

    /**
     * Тест: Получение списка коммерческих объектов
     */
    public function test_get_commercial_blocks_list(): void
    {
        CommercialBlock::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-commercial-1-' . uniqid(),
            'name' => 'Тестовый коммерческий объект',
            'address' => 'Тестовый адрес',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/commercial-blocks');

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
     * Тест: Создание коммерческого объекта
     */
    public function test_create_commercial_block(): void
    {
        $guid = 'test-commercial-block-1-' . uniqid();
        $data = [
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => $guid,
            'name' => 'Тестовый коммерческий объект',
            'address' => 'Тестовый адрес',
            'is_active' => true,
            'data_source' => 'manual',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/commercial-blocks', $data);

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

        $this->assertDatabaseHas('commercial_blocks', [
            'guid' => $guid,
            'name' => 'Тестовый коммерческий объект',
        ]);
    }

    /**
     * Тест: Получение коммерческого объекта по ID
     */
    public function test_get_commercial_block_by_id(): void
    {
        $block = CommercialBlock::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-commercial-1-' . uniqid(),
            'name' => 'Тестовый объект',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/commercial-blocks/{$block->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $block->id,
                ],
            ]);
    }

    /**
     * Тест: Обновление коммерческого объекта
     */
    public function test_update_commercial_block(): void
    {
        $block = CommercialBlock::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-commercial-1-' . uniqid(),
            'name' => 'Старое название',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/commercial-blocks/{$block->id}", [
            'name' => 'Новое название',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('commercial_blocks', [
            'id' => $block->id,
            'name' => 'Новое название',
        ]);
    }

    /**
     * Тест: Удаление коммерческого объекта
     */
    public function test_delete_commercial_block(): void
    {
        $block = CommercialBlock::create([
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-commercial-1-' . uniqid(),
            'name' => 'Тестовый объект',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/commercial-blocks/{$block->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('commercial_blocks', [
            'id' => $block->id,
        ]);
    }
}

