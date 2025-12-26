<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'theme.required' => 'Тема тикета обязательна',
            'theme.max' => 'Тема не должна превышать 255 символов',
            'message.required' => 'Сообщение обязательно',
            'attachments.*.max' => 'Размер файла не должен превышать 10 МБ',
            'attachments.*.mimes' => 'Разрешены только изображения, PDF и документы',
        ];
    }
}
