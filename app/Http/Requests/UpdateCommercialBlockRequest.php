<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommercialBlockRequest extends FormRequest
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
        $blockId = $this->route('commercialBlock')->id ?? null;
        
        return [
            'city_id' => ['sometimes', 'required', 'exists:cities,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            'district_id' => ['nullable', 'exists:regions,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            
            'guid' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('commercial_blocks', 'guid')->ignore($blockId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'external_id' => ['nullable', 'string', 'max:255'],
            
            'premises_count' => ['nullable', 'integer', 'min:0'],
            'booked_premises_count' => ['nullable', 'integer', 'min:0'],
            
            'is_new_block' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            
            'deadlines' => ['nullable', 'array'],
            'deadline_date' => ['nullable', 'date'],
            'deadline_over_check' => ['nullable', 'boolean'],
            'sales_start_at' => ['nullable', 'array'],
            
            'reward_label' => ['nullable', 'string', 'max:255'],
            
            'data_source' => ['nullable', Rule::in(['parser', 'manual', 'feed', 'import'])],
            'metadata' => ['nullable', 'array'],
            'property_types' => ['nullable', 'array'],
            'min_prices' => ['nullable', 'array'],
            
            'subway_ids' => ['nullable', 'array'],
            'subway_ids.*' => ['exists:subways,id'],
        ];
    }
}
