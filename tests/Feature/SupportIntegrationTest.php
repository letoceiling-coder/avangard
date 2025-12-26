<?php

namespace Tests\Feature;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Интеграционные тесты с реальным CRM
 * Тестирует отправку и прием данных от CRM
 */
class SupportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected string $deployToken;
    protected string $crmUrl;

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

        // Получаем токены из .env или используем тестовые
        $this->deployToken = env('DEPLOY_TOKEN', 'test-deploy-token-12345678901234567890');
        $this->crmUrl = env('APP_CRM_URL', 'https://crm.siteaccess.ru/api/v1/ticket');
        
        config(['app.deploy_token' => $this->deployToken]);
        config(['app.crm_url' => $this->crmUrl]);
        config(['app.project_identifier' => env('APP_PROJECT_IDENTIFIER', 'tma')]);
        
        putenv("DEPLOY_TOKEN={$this->deployToken}");

        Storage::fake('public');
    }

    /**
     * Тест: Создание тикета и отправка в CRM
     */
    public function test_create_ticket_and_send_to_crm(): void
    {
        // Мокаем HTTP запрос к CRM
        Http::fake([
            $this->crmUrl => Http::response(['success' => true], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/ticket', [
            'theme' => 'Интеграционный тест',
            'message' => 'Тестовое сообщение для CRM',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Проверяем что тикет создан
        $ticket = SupportTicket::where('theme', 'Интеграционный тест')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('open', $ticket->status);

        // Проверяем что был запрос к CRM
        Http::assertSent(function ($request) use ($ticket) {
            return $request->url() === $this->crmUrl
                && $request->hasHeader('Authorization', "Bearer {$this->deployToken}")
                && $request['ticket_id'] === $ticket->id
                && $request['theme'] === 'Интеграционный тест';
        });
    }

    /**
     * Тест: Создание тикета с файлами и отправка в CRM
     */
    public function test_create_ticket_with_files_and_send_to_crm(): void
    {
        Http::fake([
            $this->crmUrl => Http::response(['success' => true], 200),
        ]);

        $image = UploadedFile::fake()->image('test.png', 800, 600);
        $pdf = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/ticket', [
            'theme' => 'Тикет с файлами',
            'message' => 'Сообщение с вложениями',
            'attachments' => [$image, $pdf],
        ]);

        $response->assertStatus(201);

        $ticket = SupportTicket::where('theme', 'Тикет с файлами')->first();
        $this->assertNotNull($ticket);

        // Проверяем что файлы были отправлены в CRM
        Http::assertSent(function ($request) use ($ticket) {
            return $request->url() === $this->crmUrl
                && isset($request['attachments'])
                && is_array($request['attachments'])
                && count($request['attachments']) === 2;
        });
    }

    /**
     * Тест: Получение сообщения от CRM через webhook
     */
    public function test_receive_message_from_crm_webhook(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Ответ от CRM системы',
            'attachments' => [
                [
                    'name' => 'solution.pdf',
                    'url' => 'https://crm.siteaccess.ru/files/solution.pdf',
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
                    'message' => 'Ответ от CRM системы',
                ],
            ]);

        // Проверяем что сообщение сохранено
        $message = SupportMessage::where('ticket_id', $ticket->id)
            ->where('sender', 'crm')
            ->first();
        $this->assertNotNull($message);
        $this->assertEquals('Ответ от CRM системы', $message->message);
        $this->assertNotNull($message->attachments);
    }

    /**
     * Тест: Изменение статуса тикета от CRM
     */
    public function test_change_ticket_status_from_crm(): void
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
     * Тест: Полный цикл - создание тикета, ответ от CRM, изменение статуса
     */
    public function test_full_cycle_ticket_creation_and_crm_interaction(): void
    {
        // Шаг 1: Создаем тикет
        Http::fake([
            $this->crmUrl => Http::response(['success' => true], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->post('/api/v1/support/ticket', [
            'theme' => 'Полный цикл тест',
            'message' => 'Начальное сообщение',
        ]);

        $response->assertStatus(201);
        $ticketId = $response->json('data.id');

        // Шаг 2: CRM изменяет статус на "в работе"
        $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/status', [
            'ticket_id' => $ticketId,
            'status' => 'in_progress',
        ])->assertStatus(200);

        // Шаг 3: CRM отправляет ответ
        $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticketId,
            'message' => 'Мы работаем над вашей проблемой',
        ])->assertStatus(201);

        // Шаг 4: Админ отправляет дополнительное сообщение
        $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/message', [
            'ticket_id' => $ticketId,
            'message' => 'Спасибо за помощь',
        ])->assertStatus(201);

        // Шаг 5: CRM закрывает тикет
        $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/status', [
            'ticket_id' => $ticketId,
            'status' => 'closed',
        ])->assertStatus(200);

        // Проверяем финальное состояние
        $ticket = SupportTicket::find($ticketId);
        $this->assertEquals('closed', $ticket->status);
        $this->assertFalse($ticket->isChatEnabled());

        // Проверяем что все сообщения сохранены
        $messages = SupportMessage::where('ticket_id', $ticketId)->get();
        $this->assertGreaterThanOrEqual(2, $messages->count());
    }

    /**
     * Тест: Несколько сообщений от CRM подряд
     */
    public function test_multiple_messages_from_crm(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        // Отправляем несколько сообщений от CRM
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->withHeaders([
                'Authorization' => "Bearer {$this->deployToken}",
                'Accept' => 'application/json',
            ])->postJson('/api/support/webhook/message', [
                'ticket_id' => $ticket->id,
                'message' => "Сообщение от CRM #{$i}",
            ]);

            $response->assertStatus(201);
        }

        // Проверяем что все сообщения сохранены
        $messages = SupportMessage::where('ticket_id', $ticket->id)
            ->where('sender', 'crm')
            ->get();
        $this->assertCount(3, $messages);
    }

    /**
     * Тест: Отправка сообщения в закрытый тикет (должно быть заблокировано)
     */
    public function test_cannot_send_message_to_closed_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['status' => 'closed']);

        // Попытка отправить сообщение от админа
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->postJson('/api/v1/support/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Попытка отправить в закрытый',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Чат недоступен для закрытых тикетов',
            ]);
    }

    /**
     * Тест: Webhook с неверным токеном
     */
    public function test_webhook_with_invalid_token(): void
    {
        $ticket = SupportTicket::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-token',
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticket->id,
            'message' => 'Тест',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Тест: Валидация данных webhook
     */
    public function test_webhook_validation(): void
    {
        // Неверный UUID
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => 'invalid-uuid',
            'message' => 'Тест',
        ]);

        $response->assertStatus(422);

        // Отсутствует сообщение
        $ticket = SupportTicket::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->deployToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/support/webhook/message', [
            'ticket_id' => $ticket->id,
        ]);

        $response->assertStatus(422);
    }
}

