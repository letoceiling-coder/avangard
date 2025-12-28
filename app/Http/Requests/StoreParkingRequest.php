<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'block_id' => ['nullable', 'exists:blocks,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:regions,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            
            'external_id' => ['nullable', 'string', 'max:255'],
            'block_guid' => ['nullable', 'string', 'max:255'],
            'block_name' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'area' => ['nullable', 'numeric', 'min:0'],
            
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            'parking_type' => ['nullable', 'string', 'max:255'],
            'place_type' => ['nullable', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'status_label' => ['nullable', 'string', 'max:255'],
            
            'price' => ['nullable', 'integer', 'min:0'],
            'reward_label' => ['nullable', 'string', 'max:255'],
            
            'deadline' => ['nullable', 'string', 'max:255'],
            'deadline_date' => ['nullable', 'date'],
            'deadline_over_check' => ['nullable', 'boolean'],
            
            'data_source' => ['nullable', Rule::in(['parser', 'manual', 'feed', 'import'])],
            'metadata' => ['nullable', 'array'],
            
            'subway_ids' => ['nullable', 'array'],
            'subway_ids.*' => ['exists:subways,id'],
        ];
    }
    
    protected function prepareForValidation(): void
    {
        if (!$this->has('data_source')) {
            $this->merge(['data_source' => 'manual']);
        }
        
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
        
        if (!$this->has('status')) {
            $this->merge(['status' => 'available']);
        }
    }
}

