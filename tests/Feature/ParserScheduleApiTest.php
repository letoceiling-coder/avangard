<?php

namespace Tests\Feature;

use App\Models\ParserSchedule;
use App\Models\Trend\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParserScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected City $city;

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
    }

    /**
     * Тест: Получение списка расписаний парсера
     */
    public function test_get_parser_schedules_list(): void
    {
        ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$this->city->id],
            'time_from' => '09:00',
            'time_to' => '18:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/parser-schedules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'object_type',
                        'object_type_name',
                        'city_ids',
                        'time_from',
                        'time_to',
                        'days_of_week',
                        'is_active',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Тест: Создание расписания парсера
     */
    public function test_create_parser_schedule(): void
    {
        $data = [
            'object_type' => 'blocks',
            'city_ids' => [$this->city->id],
            'time_from' => '09:00',
            'time_to' => '18:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
            'description' => 'Ежедневный парсинг квартир',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/parser-schedules', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'object_type',
                    'object_type_name',
                    'city_ids',
                    'is_active',
                ],
            ]);

        $this->assertDatabaseHas('parser_schedules', [
            'object_type' => 'blocks',
            'is_active' => true,
        ]);
    }

    /**
     * Тест: Получение расписания парсера по ID
     */
    public function test_get_parser_schedule_by_id(): void
    {
        $schedule = ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$this->city->id],
            'time_from' => '09:00',
            'time_to' => '18:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get("/api/v1/parser-schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $schedule->id,
                ],
            ]);
    }

    /**
     * Тест: Обновление расписания парсера
     */
    public function test_update_parser_schedule(): void
    {
        $schedule = ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$this->city->id],
            'time_from' => '09:00',
            'time_to' => '18:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->putJson("/api/v1/parser-schedules/{$schedule->id}", [
            'time_from' => '10:00',
            'time_to' => '19:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('parser_schedules', [
            'id' => $schedule->id,
            'time_from' => '10:00',
            'time_to' => '19:00',
        ]);
    }

    /**
     * Тест: Удаление расписания парсера
     */
    public function test_delete_parser_schedule(): void
    {
        $schedule = ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$this->city->id],
            'time_from' => '09:00',
            'time_to' => '18:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->delete("/api/v1/parser-schedules/{$schedule->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('parser_schedules', [
            'id' => $schedule->id,
        ]);
    }
}

