<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlotRequest extends FormRequest
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
        $plotId = $this->route('plot')->id ?? null;
        
        return [
            'village_id' => ['nullable', 'exists:villages,id'],
            'city_id' => ['sometimes', 'required', 'exists:cities,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            
            'guid' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('plots', 'guid')->ignore($plotId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'crm_id' => ['nullable', 'string', 'max:255'],
            
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            
            'area_min' => ['nullable', 'numeric', 'min:0'],
            'area_max' => ['nullable', 'numeric', 'min:0'],
            
            'status' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            
            'data_source' => ['nullable', Rule::in(['parser', 'manual', 'feed', 'import'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
