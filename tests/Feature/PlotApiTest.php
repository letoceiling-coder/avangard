<?php

namespace Tests\Feature;

use App\Models\Trend\Plot;
use App\Models\Trend\Village;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlotApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected City $city;
    protected Builder $builder;
    protected Village $village;

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
            ['guid' => 'test-builder-plot-' . $this->user->id],
            [
                'name' => 'Тестовый застройщик',
                'is_active' => true,
            ]
        );

        $this->village = Village::firstOrCreate(
            ['guid' => 'test-village-plot-' . $this->user->id],
            [
                'city_id' => $this->city->id,
                'builder_id' => $this->builder->id,
                'name' => 'Тестовый поселок',
                'is_active' => true,
                'data_source' => 'manual',
            ]
        );
    }

    /**
     * Тест: Получение списка участков
     */
    public function test_get_plots_list(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Plot::create([
                'city_id' => $this->city->id,
                'village_id' => $this->village->id,
                'builder_id' => $this->builder->id,
                'guid' => "test-plot-{$i}-" . uniqid(),
                'name' => "Тестовый участок {$i}",
                'is_active' => true,
                'data_source' => 'manual',
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/plots');

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
     * Тест: Создание участка
     */
    public function test_create_plot(): void
    {
        $guid = 'test-plot-1-' . uniqid();
        $data = [
            'city_id' => $this->city->id,
            'village_id' => $this->village->id,
            'builder_id' => $this->builder->id,
            'guid' => $guid,
            'name' => 'Тестовый участок',
            'address' => 'Тестовый адрес',
            'min_price' => 5000000,
            'max_price' => 8000000,
            'area_min' => 500,
            'area_max' => 800,
            'is_active' => true,
            'data_source' => 'manual',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/plots', $data);

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

        $this->assertDatabaseHas('plots', [
            'guid' => $guid,
            'name' => 'Тестовый участок',
        ]);
    }

    /**
     * Тест: Получение участка по ID
     */
    public function test_get_plot_by_id(): void
    {
        $plot =         Plot::create([
            'city_id' => $this->city->id,
            'village_id' => $this->village->id,
            'builder_id' => $this->builder->id,
            'guid' => 'test-plot-1-' . uniqid(),
            'name' => 'Тестовый участок',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/plots/{$plot->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $plot->id,
                ],
            ]);
    }

    /**
     * Тест: Обновление участка
     */
    public function test_update_plot(): void
    {
        $plot =         Plot::create([
            'city_id' => $this->city->id,
            'village_id' => $this->village->id,
            'guid' => 'test-plot-update-' . uniqid(),
            'name' => 'Старое название',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/plots/{$plot->id}", [
            'name' => 'Новое название',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plots', [
            'id' => $plot->id,
            'name' => 'Новое название',
        ]);
    }

    /**
     * Тест: Удаление участка
     */
    public function test_delete_plot(): void
    {
        $plot =         Plot::create([
            'city_id' => $this->city->id,
            'village_id' => $this->village->id,
            'guid' => 'test-plot-delete-' . uniqid(),
            'name' => 'Участок для удаления',
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/plots/{$plot->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('plots', [
            'id' => $plot->id,
        ]);
    }

    /**
     * Тест: Фильтрация по цене
     */
    public function test_filter_plots_by_price(): void
    {
        Plot::create([
            'city_id' => $this->city->id,
            'village_id' => $this->village->id,
            'guid' => 'test-plot-price1-' . uniqid(),
            'name' => 'Участок 1',
            'min_price' => 3000000,
            'max_price' => 5000000,
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        Plot::create([
            'city_id' => $this->city->id,
            'village_id' => $this->village->id,
            'guid' => 'test-plot-price2-' . uniqid(),
            'name' => 'Участок 2',
            'min_price' => 7000000,
            'max_price' => 10000000,
            'is_active' => true,
            'data_source' => 'manual',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/plots?min_price=4000000&max_price=8000000');

        $response->assertStatus(200);
    }
}

