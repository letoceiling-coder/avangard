<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Log;

class SupportLogger
{
    /**
     * Логирование создания тикета
     */
    public static function logTicketCreated(SupportTicket $ticket, array $context = []): void
    {
        try {
            Log::channel('tickets')->info('Ticket created', array_merge([
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject ?? $ticket->theme ?? null,
                'status' => $ticket->status,
                'user_id' => auth()->check() ? auth()->id() : null,
                'user_email' => auth()->check() ? auth()->user()?->email : null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log ticket creation', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование отправки тикета в CRM
     */
    public static function logTicketSentToCrm(SupportTicket $ticket, bool $success, array $context = []): void
    {
        try {
            $level = $success ? 'info' : 'error';
            $crmUrl = config('app.crm_url', env('APP_CRM_URL', 'https://crm.siteaccess.ru/api/v1/support/ticket'));
            
            Log::channel('tickets')->{$level}('Ticket sent to CRM', array_merge([
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject ?? $ticket->theme ?? null,
                'status' => $ticket->status,
                'success' => $success,
                'crm_url' => $crmUrl ?: 'not configured',
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log ticket sent to CRM', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование создания сообщения
     */
    public static function logMessageCreated(SupportMessage $message, array $context = []): void
    {
        try {
            Log::channel('tickets')->info('Message created', array_merge([
                'message_id' => $message->id,
                'ticket_id' => $message->ticket_id,
                'sender' => $message->sender,
                'has_attachments' => !empty($message->attachments),
                'attachments_count' => is_array($message->attachments) ? count($message->attachments) : 0,
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => request()->ip(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log message creation', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование изменения статуса тикета
     */
    public static function logTicketStatusChanged(SupportTicket $ticket, string $oldStatus, string $newStatus, array $context = []): void
    {
        try {
            Log::channel('tickets')->info('Ticket status changed', array_merge([
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject ?? $ticket->theme ?? null,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $context['changed_by'] ?? (auth()->check() ? 'admin' : 'crm'),
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => request()->ip(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log status change', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование получения сообщения от CRM
     */
    public static function logMessageReceivedFromCrm(SupportMessage $message, array $context = []): void
    {
        Log::channel('tickets')->info('Message received from CRM', array_merge([
            'message_id' => $message->id,
            'ticket_id' => $message->ticket_id,
            'has_attachments' => !empty($message->attachments),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }

    /**
     * Логирование ошибки
     */
    public static function logError(string $action, \Exception $e, array $context = []): void
    {
        Log::channel('tickets')->error("Error: {$action}", array_merge([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'ip' => request()->ip(),
        ], $context));
    }

    /**
     * Логирование получения списка тикетов
     */
    public static function logTicketsListed(array $filters, int $count, array $context = []): void
    {
        try {
            Log::channel('tickets')->debug('Tickets list requested', array_merge([
                'filters' => $filters,
                'count' => $count,
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => request()->ip(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log tickets list', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование получения тикета
     */
    public static function logTicketViewed(SupportTicket $ticket, array $context = []): void
    {
        try {
            Log::channel('tickets')->debug('Ticket viewed', array_merge([
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject ?? $ticket->theme ?? null,
                'status' => $ticket->status,
                'messages_count' => $ticket->messages()->count(),
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => request()->ip(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log ticket view', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование попытки отправки в закрытый тикет
     */
    public static function logClosedTicketMessageAttempt(SupportTicket $ticket, array $context = []): void
    {
        try {
            Log::channel('tickets')->warning('Attempt to send message to closed ticket', array_merge([
                'ticket_id' => $ticket->id,
                'status' => $ticket->status,
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => request()->ip(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log closed ticket attempt', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование валидации
     */
    public static function logValidationError(string $action, array $errors, array $context = []): void
    {
        try {
            Log::channel('tickets')->warning("Validation error: {$action}", array_merge([
                'errors' => $errors,
                'user_id' => auth()->check() ? auth()->id() : null,
                'ip' => request()->ip(),
            ], $context));
        } catch (\Exception $e) {
            // Если логирование не удалось, не прерываем выполнение
            Log::channel('tickets')->error('Failed to log validation error', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование неавторизованного доступа
     */
    public static function logUnauthorizedAccess(string $action, array $context = []): void
    {
        Log::channel('tickets')->warning("Unauthorized access: {$action}", array_merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ], $context));
    }

    /**
     * Логирование webhook запроса
     */
    public static function logWebhookRequest(string $type, array $data, bool $success, array $context = []): void
    {
        $level = $success ? 'info' : 'error';
        Log::channel('tickets')->{$level}("Webhook: {$type}", array_merge([
            'type' => $type,
            'data' => $data,
            'success' => $success,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }
}

