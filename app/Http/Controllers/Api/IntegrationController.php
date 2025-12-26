<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    /**
     * Получить сообщение от CRM
     * POST /api/integration/messages
     */
    public function receiveMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'external_ticket_id' => 'required|uuid',
            'message' => 'required_without:attachments|string|nullable',
            'attachments' => 'nullable|array',
            'sender' => ['required', Rule::in(['tma', 'crm'])],
        ]);

        try {
            // Находим тикет по external_id или по id
            $ticket = SupportTicket::where('external_id', $validated['external_ticket_id'])
                ->orWhere('id', $validated['external_ticket_id'])
                ->firstOrFail();

            // Проверка на дубликат по external_message_id (если придет)
            if ($request->has('external_message_id')) {
                $existing = SupportMessage::where('external_message_id', $request->input('external_message_id'))
                    ->where('ticket_id', $ticket->id)
                    ->first();
                
                if ($existing) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Message already exists',
                        'data' => $existing,
                    ], 200);
                }
            }

            DB::beginTransaction();

            $message = SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender' => $validated['sender'],
                'body' => $validated['message'] ?? null,
                'attachments' => $validated['attachments'] ?? null,
                'external_message_id' => $request->input('external_message_id'),
                'created_at' => now(),
            ]);

            SupportLogger::logMessageReceivedFromCrm($message);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message received',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            SupportLogger::logError('Processing integration message', $e, $validated);

            return response()->json([
                'success' => false,
                'message' => 'Error processing message',
            ], 500);
        }
    }

    /**
     * Получить изменение статуса от CRM
     * POST /api/integration/status
     */
    public function receiveStatusChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'external_ticket_id' => 'required|uuid',
            'status' => ['required', Rule::in(['open', 'in_progress', 'closed'])],
        ]);

        try {
            $ticket = SupportTicket::where('external_id', $validated['external_ticket_id'])
                ->orWhere('id', $validated['external_ticket_id'])
                ->firstOrFail();

            DB::beginTransaction();

            $oldStatus = $ticket->status;
            $ticket->update(['status' => $validated['status']]);

            SupportLogger::logTicketStatusChanged($ticket, $oldStatus, $validated['status'], [
                'changed_by' => 'crm',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            SupportLogger::logError('Updating ticket status from integration', $e, $validated);

            return response()->json([
                'success' => false,
                'message' => 'Error updating status',
            ], 500);
        }
    }
}







