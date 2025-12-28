<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVillageRequest extends FormRequest
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
            'city_id' => ['required', 'exists:cities,id'],
            'builder_id' => ['nullable', 'exists:builders,id'],
            
            'guid' => ['required', 'string', 'max:255', 'unique:villages,guid'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'external_id' => ['nullable', 'string', 'max:255'],
            
            'plots_count' => ['nullable', 'integer', 'min:0'],
            'view_plots_count' => ['nullable', 'integer', 'min:0'],
            'distance' => ['nullable', 'array'],
            
            'deadline' => ['nullable', 'string', 'max:255'],
            'deadline_date' => ['nullable', 'date'],
            'sales_start' => ['nullable', 'string', 'max:255'],
            'sales_start_date' => ['nullable', 'date'],
            
            'reward_label' => ['nullable', 'string', 'max:255'],
            
            'is_new_village' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            
            'data_source' => ['nullable', Rule::in(['parser', 'manual', 'feed', 'import'])],
            'metadata' => ['nullable', 'array'],
            'property_types' => ['nullable', 'array'],
        ];
    }
}
