<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
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
            'name' => ['required','unique:folders,name','string','max:255'],
            'slug' => ['nullable'],
            'parent_id' => ['nullable','int'],

        ];
    }
    protected function prepareForValidation() {
        $this->merge([
            'slug' => $this->slug ? $this->slug  : str(strtolower($this->name))->slug(),

        ]);
    }
    public function messages()
    {
        return [

            'name.unique' => "Папка с таким наименованием существует",
            'name.required' => "Заполните наименование папки",

        ];
    }
}
