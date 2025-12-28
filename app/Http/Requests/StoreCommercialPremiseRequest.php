<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommercialPremiseRequest extends FormRequest
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
            'commercial_block_id' => ['nullable', 'exists:commercial_blocks,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            'district_id' => ['nullable', 'exists:regions,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            
            'guid' => ['required', 'string', 'max:255', 'unique:commercial_premises,guid'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'crm_id' => ['nullable', 'string', 'max:255'],
            
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            'price' => ['nullable', 'integer', 'min:0'],
            'price_per_sqm' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'numeric', 'min:0'],
            
            'premise_type' => ['nullable', 'string', 'max:255'],
            'property_types' => ['nullable', 'array'],
            'building_type' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'ceiling_height' => ['nullable', 'numeric', 'min:0'],
            
            'status' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_booked' => ['nullable', 'boolean'],
            'is_sold' => ['nullable', 'boolean'],
            
            'data_source' => ['nullable', Rule::in(['parser', 'manual', 'feed', 'import'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
