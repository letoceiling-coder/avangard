<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParserScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'object_type' => ['sometimes', 'required', Rule::in(array_keys(\App\Models\ParserSchedule::OBJECT_TYPES))],
            'city_ids' => ['nullable', 'array'],
            'city_ids.*' => ['exists:cities,id'],
            'time_from' => ['sometimes', 'required', 'date_format:H:i'],
            'time_to' => ['required_with:time_from', 'date_format:H:i', 'after:time_from'],
            'days_of_week' => ['sometimes', 'required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'is_active' => ['nullable', 'boolean'],
            'check_images' => ['nullable', 'boolean'],
            'force_update' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'skip_errors' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
