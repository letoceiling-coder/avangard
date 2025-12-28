<?php

namespace Tests\Feature;

use App\Models\ParserError;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParserErrorApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

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
    }

    /**
     * Тест: Получение списка ошибок парсера
     */
    public function test_get_parser_errors_list(): void
    {
        ParserError::factory()->count(5)->create([
            'is_resolved' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/parser-errors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'error_type',
                        'object_type',
                        'error_message',
                        'is_resolved',
                    ],
                ],
            ]);
    }

    /**
     * Тест: Фильтрация ошибок по типу
     */
    public function test_get_parser_errors_filtered_by_type(): void
    {
        ParserError::factory()->count(3)->create([
            'error_type' => 'api',
            'is_resolved' => false,
        ]);

        ParserError::factory()->count(2)->create([
            'error_type' => 'parsing',
            'is_resolved' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/parser-errors?error_type=api');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $error) {
            $this->assertEquals('api', $error['error_type']);
        }
    }

    /**
     * Тест: Получение статистики ошибок
     */
    public function test_get_parser_errors_statistics(): void
    {
        ParserError::factory()->count(10)->create(['is_resolved' => false]);
        ParserError::factory()->count(5)->create(['is_resolved' => true]);
        ParserError::factory()->count(3)->create([
            'error_type' => 'api',
            'is_resolved' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->get('/api/v1/parser-errors/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'unresolved',
                'by_type',
                'by_object_type',
                'recent',
            ]);

        $data = $response->json();
        $this->assertEquals(18, $data['total']);
        $this->assertEquals(13, $data['unresolved']);
    }

    /**
     * Тест: Пометить ошибку как решенную
     */
    public function test_resolve_parser_error(): void
    {
        $error = ParserError::factory()->create([
            'is_resolved' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson("/api/v1/parser-errors/{$error->id}/resolve", [
            'notes' => 'Проблема исправлена',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($error->fresh()->is_resolved);
        $this->assertNotNull($error->fresh()->resolved_at);
    }
}

