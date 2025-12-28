<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Можно добавить проверку прав доступа
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            
            'guid' => ['required', 'string', 'max:255', 'unique:blocks,guid'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'crm_id' => ['nullable', 'integer'],
            'external_id' => ['nullable', 'string', 'max:255'],
            
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            'status' => ['nullable', 'integer'],
            'edit_mode' => ['nullable', 'integer'],
            'is_suite' => ['nullable', 'boolean'],
            'is_exclusive' => ['nullable', 'boolean'],
            'is_marked' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            
            'apartments_count' => ['nullable', 'integer', 'min:0'],
            'view_apartments_count' => ['nullable', 'integer', 'min:0'],
            'exclusive_apartments_count' => ['nullable', 'integer', 'min:0'],
            
            'deadline' => ['nullable', 'string', 'max:255'],
            'deadline_date' => ['nullable', 'date'],
            'deadline_over_check' => ['nullable', 'boolean'],
            'finishing' => ['nullable', 'string', 'max:255'],
            
            'data_source' => ['nullable', Rule::in(['parser', 'manual', 'feed', 'import'])],
            
            'metadata' => ['nullable', 'array'],
            'advantages' => ['nullable', 'array'],
            'payment_types' => ['nullable', 'array'],
            'contract_types' => ['nullable', 'array'],
            'installments' => ['nullable', 'array'],
            
            // Связи
            'subway_ids' => ['nullable', 'array'],
            'subway_ids.*' => ['exists:subways,id'],
        ];
    }
    
    protected function prepareForValidation(): void
    {
        // Если не указан источник, ставим manual
        if (!$this->has('data_source')) {
            $this->merge(['data_source' => 'manual']);
        }
        
        // Если не указан is_active, ставим true
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
        
        // Если не указан status, ставим 1 (активен)
        if (!$this->has('status')) {
            $this->merge(['status' => 1]);
        }
    }
}

