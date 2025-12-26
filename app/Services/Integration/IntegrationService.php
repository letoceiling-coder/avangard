<?php

namespace App\Services\Integration;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntegrationService
{
    protected string $crmUrl;
    protected ?string $deployToken;
    protected string $projectIdentifier;

    public function __construct()
    {
        // Базовый URL CRM без пути к API
        $this->crmUrl = config('app.crm_url', env('APP_CRM_URL', 'https://crm.siteaccess.ru'));
        // Убираем путь из URL, если он есть
        $this->crmUrl = rtrim(preg_replace('#/api/.*$#', '', $this->crmUrl), '/');
        $this->deployToken = config('app.deploy_token') ?: env('DEPLOY_TOKEN') ?: null;
        $this->projectIdentifier = config('app.project_identifier', env('APP_PROJECT_IDENTIFIER', 'tma'));
    }

    /**
     * Отправить тикет в CRM
     */
    public function sendTicketToCrm(SupportTicket $ticket, array $attachments = []): ?string
    {
        if (!$this->deployToken) {
            Log::channel('tickets')->error('DEPLOY_TOKEN not configured');
            return null;
        }

        try {
            // Используем новый API интеграции (внутри v1 префикса)
            $url = rtrim($this->crmUrl, '/') . '/api/v1/integration/tickets';
            
            $payload = [
                'external_ticket_id' => $ticket->id,
                'subject' => $ticket->subject ?? $ticket->theme ?? '',
                'message' => $ticket->messages()->first()?->body ?? '',
                'attachments' => $attachments,
                'external_url' => config('app.url'),
                'project' => $this->projectIdentifier,
            ];

            $response = Http::withToken($this->deployToken)
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $crmTicketId = $data['data']['id'] ?? null;
                
                if ($crmTicketId) {
                    $ticket->update(['external_id' => $crmTicketId]);
                }

                Log::channel('tickets')->info('Ticket sent to CRM', [
                    'ticket_id' => $ticket->id,
                    'crm_ticket_id' => $crmTicketId,
                ]);

                return $crmTicketId;
            }

            Log::channel('tickets')->error('Failed to send ticket to CRM', [
                'ticket_id' => $ticket->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::channel('tickets')->error('Exception sending ticket to CRM', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Отправить сообщение в CRM
     */
    public function sendMessageToCrm(SupportMessage $message): bool
    {
        if (!$this->deployToken) {
            return false;
        }

        $ticket = $message->ticket;
        
        if (!$ticket->external_id) {
            Log::channel('tickets')->warning('Cannot send message: ticket has no external_id', [
                'ticket_id' => $ticket->id,
                'message_id' => $message->id,
            ]);
            return false;
        }

        // Проверка на дубликат
        if ($message->external_message_id) {
            Log::channel('tickets')->debug('Message already sent to CRM', [
                'message_id' => $message->id,
                'external_message_id' => $message->external_message_id,
            ]);
            return true;
        }

        try {
            // Используем новый API интеграции (внутри v1 префикса)
            $url = rtrim($this->crmUrl, '/') . '/api/v1/integration/messages';
            
            $messageText = $message->body ?? $message->message ?? '';
            
            $payload = [
                'external_ticket_id' => $ticket->external_id,
                'message' => $messageText, // Отправляем как message для CRM
                'body' => $messageText, // Также отправляем как body для совместимости
                'attachments' => $message->attachments ?? [],
                'sender' => 'tma',
                'external_message_id' => $message->id,
            ];

            Log::channel('tickets')->debug('IntegrationService: Sending message to CRM', [
                'url' => $url,
                'ticket_external_id' => $ticket->external_id,
                'message_id' => $message->id,
                'has_message_text' => !empty($messageText),
                'message_length' => strlen($messageText),
                'has_attachments' => !empty($message->attachments),
                'attachments_count' => is_array($message->attachments) ? count($message->attachments) : 0,
            ]);

            $response = Http::withToken($this->deployToken)
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $crmMessageId = $data['data']['id'] ?? null;
                
                if ($crmMessageId) {
                    $message->update(['external_message_id' => $crmMessageId]);
                }

                Log::channel('tickets')->info('Message sent to CRM', [
                    'message_id' => $message->id,
                    'crm_message_id' => $crmMessageId,
                ]);

                return true;
            }

            Log::channel('tickets')->error('Failed to send message to CRM', [
                'message_id' => $message->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('tickets')->error('Exception sending message to CRM', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

