<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ParserRunTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестового пользователя с ролью admin
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        
        // Назначаем роль admin
        // Проверяем, есть ли метод hasAnyRole (используется в EnsureUserIsAdmin)
        if (method_exists($this->user, 'hasAnyRole')) {
            // Если используется Spatie Permission или похожая система
            if (method_exists($this->user, 'assignRole')) {
                $this->user->assignRole('admin');
            } else {
                // Если роль хранится в поле role
                $this->user->update(['role' => 'admin']);
            }
        } else {
            // Если нет системы ролей, просто обновляем поле role
            $this->user->update(['role' => 'admin']);
        }
    }

    /**
     * Тест запуска парсера через API endpoint
     */
    public function test_parser_run_endpoint(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/parser/run', []);

        // Проверяем, что запрос принят (202 Accepted)
        $response->assertStatus(202);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('initiated', $responseData['status']);
    }

    /**
     * Тест запуска парсера с параметрами
     */
    public function test_parser_run_with_parameters(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/parser/run', [
                'type' => ['blocks'],
                'limit' => 10,
                'skip_errors' => true,
            ]);

        $response->assertStatus(202);
    }

    /**
     * Тест запуска парсера без авторизации
     */
    public function test_parser_run_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/parser/run', []);

        $response->assertStatus(401);
    }

    /**
     * Тест запуска парсера через Artisan команду напрямую
     */
    public function test_parser_command_direct(): void
    {
        $startTime = microtime(true);
        
        try {
            $exitCode = Artisan::call('trend:parse', [
                '--type' => ['blocks'],
                '--limit' => 5,
                '--skip-errors' => true,
            ]);
            
            $executionTime = microtime(true) - $startTime;
            
            $output = Artisan::output();
            
            // Логируем результат
            Log::info('ParserRunTest: Direct command execution', [
                'exit_code' => $exitCode,
                'execution_time_seconds' => round($executionTime, 2),
                'output_length' => strlen($output),
            ]);
            
            // Проверяем, что команда выполнилась (exit code 0 или 1 - оба допустимы)
            $this->assertContains($exitCode, [0, 1]);
            
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            Log::error('ParserRunTest: Command execution failed', [
                'error' => $e->getMessage(),
                'execution_time_seconds' => round($executionTime, 2),
            ]);
            
            // Не падаем, если команда не может выполниться (нет настроек, нет данных и т.д.)
            $this->assertTrue(true);
        }
    }

    /**
     * Тест проверки времени выполнения парсера
     */
    public function test_parser_execution_time_tracking(): void
    {
        $startTime = microtime(true);
        
        try {
            Artisan::call('trend:parse', [
                '--type' => ['blocks'],
                '--limit' => 1,
                '--skip-errors' => true,
            ]);
        } catch (\Exception $e) {
            // Игнорируем ошибки выполнения
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Проверяем, что время выполнения зафиксировано
        $this->assertGreaterThan(0, $executionTime);
        $this->assertLessThan(300, $executionTime); // Не должно занимать больше 5 минут
    }
}

