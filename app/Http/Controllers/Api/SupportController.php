<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\WebhookMessageRequest;
use App\Http\Requests\WebhookStatusRequest;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportService;
use App\Services\Integration\IntegrationService;
use App\Services\SupportLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    protected SupportService $supportService;
    protected IntegrationService $integrationService;

    public function __construct(SupportService $supportService, IntegrationService $integrationService)
    {
        $this->supportService = $supportService;
        $this->integrationService = $integrationService;
    }

    /**
     * Get list of tickets
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->get('status');
            $perPage = min((int) $request->get('per_page', 20), 100); // Максимум 100 на страницу

            $query = SupportTicket::query();

            // Загружаем сообщения только если они нужны
            $query->with(['messages' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }]);

            $query->orderBy('created_at', 'desc');

            if ($status && in_array($status, ['open', 'in_progress', 'closed'])) {
                $query->where('status', $status);
            }

            $tickets = $query->paginate($perPage);

            // Логирование после успешного получения данных
            try {
                SupportLogger::logTicketsListed(
                    ['status' => $status, 'per_page' => $perPage],
                    $tickets->total()
                );
            } catch (\Exception $logError) {
                // Логирование не должно прерывать выполнение
                Log::error('Failed to log tickets list', [
                    'error' => $logError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tickets,
            ]);
        } catch (\Exception $e) {
            // Логируем ошибку
            try {
                SupportLogger::logError('Fetching tickets list', $e, [
                    'status' => $request->get('status'),
                    'per_page' => $request->get('per_page'),
                ]);
            } catch (\Exception $logError) {
                // Если даже логирование не работает, пишем в основной лог
                Log::error('Error fetching tickets list', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка тикетов',
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Get single ticket with messages
     */
    public function show(string $id): JsonResponse
    {
        $ticket = SupportTicket::with(['messages' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        SupportLogger::logTicketViewed($ticket);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Create new ticket
     */
    public function store(CreateTicketRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create ticket
            $ticket = SupportTicket::create([
                'subject' => $request->input('theme'), // Пока используем theme из запроса, но сохраняем как subject
                'status' => 'open',
            ]);

            // Process attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                $attachments = $this->supportService->processAttachments($request->file('attachments'));
            }

            // Create initial message
            $message = SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender' => 'tma',
                'body' => $request->input('message'),
                'attachments' => !empty($attachments) ? $attachments : null,
                'created_at' => now(),
            ]);

            // Логирование создания тикета и сообщения
            SupportLogger::logTicketCreated($ticket, [
                'attachments_count' => count($attachments),
                'message_length' => strlen($request->input('message')),
            ]);
            SupportLogger::logMessageCreated($message);

            // Send to CRM через IntegrationService
            $crmAttachments = array_map(function ($att) {
                return [
                    'name' => $att['name'],
                    'url' => $att['url'],
                    'size' => $att['size'],
                    'mime_type' => $att['mime_type'],
                ];
            }, $attachments);

            // Отправка в CRM через новый IntegrationService
            $crmTicketId = $this->integrationService->sendTicketToCrm($ticket, $crmAttachments);
            $crmSent = !empty($crmTicketId);
            
            // Логирование результата отправки
            try {
                SupportLogger::logTicketSentToCrm($ticket, $crmSent, [
                    'attachments_count' => count($crmAttachments),
                    'crm_url' => config('app.crm_url'),
                    'has_deploy_token' => !empty(config('app.deploy_token')),
                ]);
            } catch (\Exception $logError) {
                // Логирование не должно прерывать выполнение
                Log::error('Failed to log ticket sent to CRM', [
                    'error' => $logError->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Тикет успешно создан',
                'data' => $ticket->load('messages'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            SupportLogger::logError('Creating ticket', $e, [
                'theme' => $request->input('theme'),
                'has_attachments' => $request->hasFile('attachments'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании тикета',
            ], 500);
        }
    }

    /**
     * Send message in ticket
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        try {
            $ticket = SupportTicket::findOrFail($request->input('ticket_id'));

            // Check if chat is enabled
            if (!$ticket->isChatEnabled()) {
                SupportLogger::logClosedTicketMessageAttempt($ticket);
                return response()->json([
                    'success' => false,
                    'message' => 'Чат недоступен для закрытых тикетов',
                ], 403);
            }

            DB::beginTransaction();

            // Process attachments
            $attachments = [];
            if ($request->hasFile('attachments')) {
                $attachments = $this->supportService->processAttachments($request->file('attachments'));
            }

            // Create message
            $message = SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender' => 'tma',
                'body' => $request->input('message'),
                'attachments' => !empty($attachments) ? $attachments : null,
                'created_at' => now(),
            ]);

            // Логирование создания сообщения
            SupportLogger::logMessageCreated($message, [
                'message_length' => strlen($request->input('message')),
            ]);

            // Send message to CRM через IntegrationService
            $this->integrationService->sendMessageToCrm($message);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Сообщение отправлено',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            SupportLogger::logError('Sending message', $e, [
                'ticket_id' => $request->input('ticket_id'),
                'has_attachments' => $request->hasFile('attachments'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке сообщения',
            ], 500);
        }
    }

    /**
     * Webhook: Receive message from CRM
     */
    public function webhookMessage(WebhookMessageRequest $request): JsonResponse
    {
        try {
            $ticket = SupportTicket::findOrFail($request->input('ticket_id'));

            DB::beginTransaction();

            $message = SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender' => 'crm',
                'body' => $request->input('message'),
                'attachments' => $request->input('attachments'),
                'external_message_id' => $request->input('external_message_id'),
                'created_at' => now(),
            ]);

            // Логирование получения сообщения от CRM
            SupportLogger::logMessageReceivedFromCrm($message, [
                'message_length' => strlen($request->input('message')),
            ]);
            SupportLogger::logWebhookRequest('message', [
                'ticket_id' => $ticket->id,
                'has_attachments' => !empty($request->input('attachments')),
            ], true);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Сообщение получено',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            SupportLogger::logError('Processing webhook message', $e, [
                'ticket_id' => $request->input('ticket_id'),
            ]);
            SupportLogger::logWebhookRequest('message', $request->all(), false, [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке сообщения',
            ], 500);
        }
    }

    /**
     * Webhook: Change ticket status from CRM
     */
    public function webhookStatus(WebhookStatusRequest $request): JsonResponse
    {
        try {
            $ticket = SupportTicket::findOrFail($request->input('ticket_id'));

            DB::beginTransaction();

            $oldStatus = $ticket->status;
            $newStatus = $request->input('status');
            
            $ticket->status = $newStatus;
            $ticket->save();

            // Логирование изменения статуса
            SupportLogger::logTicketStatusChanged($ticket, $oldStatus, $newStatus, [
                'changed_by' => 'crm',
            ]);
            SupportLogger::logWebhookRequest('status', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ], true);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Статус обновлен',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            SupportLogger::logError('Updating ticket status', $e, [
                'ticket_id' => $request->input('ticket_id'),
                'new_status' => $request->input('status'),
            ]);
            SupportLogger::logWebhookRequest('status', $request->all(), false, [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении статуса',
            ], 500);
        }
    }
}

