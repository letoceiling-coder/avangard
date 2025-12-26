<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Services\SupportLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportService
{
    protected string $crmUrl;
    protected ?string $deployToken;
    protected ?string $projectIdentifier;

    public function __construct()
    {
        $this->crmUrl = config('app.crm_url', env('APP_CRM_URL', 'https://crm.siteaccess.ru/api/v1/support/ticket'));
        $this->deployToken = config('app.deploy_token') ?: env('DEPLOY_TOKEN') ?: null;
        $this->projectIdentifier = config('app.project_identifier', env('APP_PROJECT_IDENTIFIER', 'default'));
    }

    /**
     * Send ticket to external CRM
     */
    public function sendTicketToCrm(SupportTicket $ticket, array $attachments = []): bool
    {
        if (!$this->deployToken) {
            Log::channel('tickets')->error('DEPLOY_TOKEN not configured', [
                'ticket_id' => $ticket->id,
                'crm_url' => $this->crmUrl,
            ]);
            return false;
        }

        if (empty($this->crmUrl)) {
            Log::channel('tickets')->error('CRM URL not configured', [
                'ticket_id' => $ticket->id,
            ]);
            return false;
        }

        try {
            $payload = [
                'ticket_id' => $ticket->id,
                'theme' => $ticket->subject ?? $ticket->theme ?? '',
                'message' => $ticket->messages()->first()?->body ?? '',
                'attachments' => $attachments,
                'project' => $this->projectIdentifier,
                'external_url' => config('app.url'), // URL основного проекта для отправки webhook обратно
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            
            // Добавляем токен в заголовок Authorization
            if ($this->deployToken) {
                $headers['Authorization'] = "Bearer {$this->deployToken}";
            }
            
            Log::channel('tickets')->debug('Sending ticket to CRM', [
                'url' => $this->crmUrl,
                'has_token' => !empty($this->deployToken),
                'token_length' => $this->deployToken ? strlen($this->deployToken) : 0,
                'payload' => $payload,
            ]);
            
            $response = Http::withHeaders($headers)->timeout(30)->post($this->crmUrl, $payload);

            if ($response->successful()) {
                Log::channel('tickets')->info('Ticket sent to CRM successfully', [
                    'ticket_id' => $ticket->id,
                    'theme' => $ticket->theme,
                    'crm_url' => $this->crmUrl,
                    'response_status' => $response->status(),
                    'response' => $response->json(),
                    'attachments_count' => count($attachments),
                ]);
                return true;
            }

            Log::channel('tickets')->error('Failed to send ticket to CRM', [
                'ticket_id' => $ticket->id,
                'theme' => $ticket->theme,
                'crm_url' => $this->crmUrl,
                'status' => $response->status(),
                'response' => $response->body(),
                'attachments_count' => count($attachments),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('tickets')->error('Exception while sending ticket to CRM', [
                'ticket_id' => $ticket->id,
                'theme' => $ticket->theme,
                'crm_url' => $this->crmUrl,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'has_deploy_token' => !empty($this->deployToken),
            ]);
            return false;
        }
    }

    /**
     * Process attachments and return array of file info
     */
    public function processAttachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $path = $file->store('support/attachments', 'public');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'url' => Storage::disk('public')->url($path),
            ];
        }

        return $attachments;
    }
}

