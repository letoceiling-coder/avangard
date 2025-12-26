<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by deploy.token middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket_id' => 'required|uuid|exists:support_tickets,id',
            'status' => ['required', Rule::in(['open', 'in_progress', 'closed'])],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_id.required' => 'ID тикета обязателен',
            'ticket_id.exists' => 'Тикет не найден',
            'status.required' => 'Статус обязателен',
            'status.in' => 'Недопустимый статус',
        ];
    }
}
