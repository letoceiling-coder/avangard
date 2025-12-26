<?php

namespace Tests\Feature;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected string $deployToken;

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

        // Устанавливаем DEPLOY_TOKEN для тестов (из .env или тестовый)
        $this->deployToken = env('DEPLOY_TOKEN', 'test-deploy-token-12345678901234567890');
        config(['app.deploy_token' => $this->deployToken]);
        putenv("DEPLOY_TOKEN={$this->deployToken}");
        
        // Настраиваем URL CRM для тестов
        config(['app.crm_url' => env('APP_CRM_URL', 'https://crm.siteaccess.ru/api/v1/ticket')]);
        config(['app.project_identifier' => env('APP_PROJECT_IDENTIFIER', 'tma')]);

        Storage::fake('public');
    }

    /**
     * Тест: Создание тикета без файлов
     */
    public function test_create_ticket_without_attachments(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/ticket', [
            'theme' => 'Тестовая тема',
            'message' => 'Тестовое сообщение',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'theme',
                    'status',
                    'created_at',
                    'updated_at',
                    'messages' => [
                        '*' => [
                            'id',
                            'ticket_id',
                            'sender',
                            'message',
                            'attachments',
                            'created_at',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'theme' => 'Тестовая тема',
                    'status' => 'open',
                ],
            ]);

        $this->assertDatabaseHas('support_tickets', [
            'theme' => 'Тестовая тема',
            'status' => 'open',
        ]);
    }

    /**
     * Тест: Создание тикета с файлами
     */
    public function test_create_ticket_with_attachments(): void
    {
        $image = UploadedFile::fake()->image('screenshot.png', 800, 600);
        $pdf = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/ticket', [
            'theme' => 'Тикет с файлами',
            'message' => 'Сообщение с вложениями',
            'attachments' => [$image, $pdf],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $ticket = SupportTicket::where('theme', 'Тикет с файлами')->first();
        $this->assertNotNull($ticket);

        $message = $ticket->messages()->first();
        $this->assertNotNull($message->attachments);
        $this->assertCount(2, $message->attachments);
    }

    /**
     * Тест: Валидация - отсутствует тема
     */
    public function test_create_ticket_validation_missing_theme(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/ticket', [
            'message' => 'Сообщение без темы',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    /**
     * Тест: Валидация - отсутствует сообщение
     */
    public function test_create_ticket_validation_missing_message(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/ticket', [
            'theme' => 'Тема без сообщения',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * Тест: Валидация - слишком большой файл
     */
    public function test_create_ticket_validation_file_too_large(): void
    {
        $largeFile = UploadedFile::fake()->create('large.pdf', 11000); // 11 МБ

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/ticket', [
            'theme' => 'Тема',
            'message' => 'Сообщение',
            'attachments' => [$largeFile],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Тест: Валидация - недопустимый тип файла
     */
    public function test_create_ticket_validation_invalid_file_type(): void
    {
        $exeFile = UploadedFile::fake()->create('virus.exe', 100);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/ticket', [
            'theme' => 'Тема',
            'message' => 'Сообщение',
            'attachments' => [$exeFile],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Тест: Получение списка тикетов
     */
    public function test_get_tickets_list(): void
    {
        SupportTicket::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/support/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'theme',
                            'status',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'current_page',
                    'last_page',
                    'total',
                ],
            ]);
    }

    /**
     * Тест: Фильтрация тикетов по статусу
     */
    public function test_get_tickets_filtered_by_status(): void
    {
        SupportTicket::factory()->create(['status' => 'open']);
        SupportTicket::factory()->create(['status' => 'closed']);
        SupportTicket::factory()->create(['status' => 'in_progress']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/support/tickets?status=open');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        foreach ($data as $ticket) {
            $this->assertEquals('open', $ticket['status']);
        }
    }

    /**
     * Тест: Получение тикета с сообщениями
     */
    public function test_get_ticket_with_messages(): void
    {
        $ticket = SupportTicket::factory()->create();
        SupportMessage::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'sender' => 'local',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson("/api/v1/support/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'theme',
                    'status',
                    'messages' => [
                        '*' => [
                            'id',
                            'sender',
                            'message',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data.messages');
    }

    /**
     * Тест: Отправка сообщения в открытый тикет
     */
    public function test_send_message_to_open_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Новое сообщение',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sender' => 'local',
                    'message' => 'Новое сообщение',
                ],
            ]);

        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'Новое сообщение',
            'sender' => 'local',
        ]);
    }

    /**
     * Тест: Отправка сообщения с файлами
     */
    public function test_send_message_with_attachments(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'open']);
        $image = UploadedFile::fake()->image('test.png');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Сообщение с файлом',
            'attachments' => [$image],
        ]);

        $response->assertStatus(201);
        $message = SupportMessage::where('ticket_id', $ticket->id)
            ->where('message', 'Сообщение с файлом')
            ->first();
        $this->assertNotNull($message->attachments);
    }

    /**
     * Тест: Невозможность отправить сообщение в закрытый тикет
     */
    public function test_cannot_send_message_to_closed_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'closed']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Попытка отправить в закрытый тикет',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Чат недоступен для закрытых тикетов',
            ]);
    }

    /**
     * Тест: Webhook - получение сообщения от CRM
     */
    public function test_webhook_receive_message_from_crm(): void
    {
        $ticket = SupportTicket::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Ответ от CRM',
            'attachments' => [
                [
                    'name' => 'solution.pdf',
                    'url' => 'http://crm.example.com/files/solution.pdf',
                    'size' => 51200,
                    'mime_type' => 'application/pdf',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sender' => 'crm',
                    'message' => 'Ответ от CRM',
                ],
            ]);

        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticket->id,
            'sender' => 'crm',
            'message' => 'Ответ от CRM',
        ]);
    }

    /**
     * Тест: Webhook - изменение статуса тикета
     */
    public function test_webhook_change_ticket_status(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/status', [
            'ticket_id' => $ticket->id,
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'in_progress',
                ],
            ]);

        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);
    }

    /**
     * Тест: Webhook - валидация статуса
     */
    public function test_webhook_status_validation(): void
    {
        $ticket = SupportTicket::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/status', [
            'ticket_id' => $ticket->id,
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Тест: Webhook - неверный токен
     */
    public function test_webhook_invalid_token(): void
    {
        $ticket = SupportTicket::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-token',
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Тест',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Тест: Webhook - отсутствие токена
     */
    public function test_webhook_missing_token(): void
    {
        $ticket = SupportTicket::factory()->create();

        $response = $this->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Тест',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Тест: Неавторизованный доступ
     */
    public function test_unauthorized_access(): void
    {
        $response = $this->getJson('/api/v1/support/tickets');

        $response->assertStatus(401);
    }

    /**
     * Тест: Получение несуществующего тикета
     */
    public function test_get_nonexistent_ticket(): void
    {
        $fakeId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson("/api/v1/support/tickets/{$fakeId}");

        $response->assertStatus(404);
    }

    /**
     * Тест: Пагинация списка тикетов
     */
    public function test_tickets_pagination(): void
    {
        SupportTicket::factory()->count(25)->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/v1/support/tickets?per_page=10&page=2');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, $data['current_page']);
        $this->assertCount(10, $data['data']);
    }

    /**
     * Тест: Сортировка сообщений по времени
     */
    public function test_messages_sorted_by_time(): void
    {
        $ticket = SupportTicket::factory()->create();
        
        $message1 = SupportMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'created_at' => now()->subHours(2),
        ]);
        $message2 = SupportMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'created_at' => now()->subHour(),
        ]);
        $message3 = SupportMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'created_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson("/api/v1/support/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $messages = $response->json('data.messages');
        $this->assertCount(3, $messages);
        // Проверяем, что сообщения отсортированы по возрастанию времени
        $this->assertEquals($message1->id, $messages[0]['id']);
        $this->assertEquals($message2->id, $messages[1]['id']);
        $this->assertEquals($message3->id, $messages[2]['id']);
    }
}

