<?php

namespace Tests\Feature;

use App\Models\Trend\CommercialPremise;
use App\Models\Trend\CommercialBlock;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialPremiseApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected City $city;
    protected Builder $builder;
    protected CommercialBlock $commercialBlock;

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
            ['guid' => 'test-builder-premise-' . $this->user->id],
            [
                'name' => 'Тестовый застройщик',
                'is_active' => true,
            ]
        );

        $this->commercialBlock = CommercialBlock::firstOrCreate(
            ['guid' => 'test-commercial-block-premise-' . $this->user->id],
            [
                'city_id' => $this->city->id,
                'builder_id' => $this->builder->id,
                'name' => 'Тестовый коммерческий объект',
                'is_active' => true,
            ]
        );
    }

    /**
     * Тест: Получение списка коммерческих помещений
     */
    public function test_get_commercial_premises_list(): void
    {
        CommercialPremise::create([
            'commercial_block_id' => $this->commercialBlock->id,
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-premise-1-' . uniqid(),
            'name' => 'Тестовое помещение',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/commercial-premises');

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
     * Тест: Создание коммерческого помещения
     */
    public function test_create_commercial_premise(): void
    {
        $guid = 'test-premise-1-' . uniqid();
        $data = [
            'commercial_block_id' => $this->commercialBlock->id,
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => $guid,
            'name' => 'Тестовое помещение',
            'address' => 'Тестовый адрес',
            'price' => 10000000,
            'area' => 50.5,
            'is_active' => true,
            'data_source' => 'manual',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/commercial-premises', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'guid',
                    'name',
                    'city',
                    'is_active',
                ],
            ]);

        $this->assertDatabaseHas('commercial_premises', [
            'guid' => $guid,
            'name' => 'Тестовое помещение',
        ]);
    }

    /**
     * Тест: Получение коммерческого помещения по ID
     */
    public function test_get_commercial_premise_by_id(): void
    {
        $premise = CommercialPremise::create([
            'commercial_block_id' => $this->commercialBlock->id,
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-premise-1-' . uniqid(),
            'name' => 'Тестовое помещение',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/commercial-premises/{$premise->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $premise->id,
                ],
            ]);
    }

    /**
     * Тест: Обновление коммерческого помещения
     */
    public function test_update_commercial_premise(): void
    {
        $premise = CommercialPremise::create([
            'commercial_block_id' => $this->commercialBlock->id,
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-premise-1-' . uniqid(),
            'name' => 'Старое название',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/commercial-premises/{$premise->id}", [
            'name' => 'Новое название',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('commercial_premises', [
            'id' => $premise->id,
            'name' => 'Новое название',
        ]);
    }

    /**
     * Тест: Удаление коммерческого помещения
     */
    public function test_delete_commercial_premise(): void
    {
        $premise = CommercialPremise::create([
            'commercial_block_id' => $this->commercialBlock->id,
            'city_id' => $this->city->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-premise-1-' . uniqid(),
            'name' => 'Тестовое помещение',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/commercial-premises/{$premise->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('commercial_premises', [
            'id' => $premise->id,
        ]);
    }
}

