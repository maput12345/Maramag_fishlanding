<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class FishTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.required' => 'The fish type name is required.',
            'name.min' => 'The fish type name must be at least 2 characters.',
            'name.max' => 'The fish type name may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'name' => 'fish type name',
            'description' => 'description',
        ];
    }
}
