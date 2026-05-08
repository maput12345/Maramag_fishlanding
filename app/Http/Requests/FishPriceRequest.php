<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FishPriceRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'broker_fish_type_id' => $this->isMethod('post')
                ? ['required', 'exists:BrokerFishTypeAssignment,id']
                : ['nullable', 'exists:BrokerFishTypeAssignment,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'default_cost_price' => ['nullable', 'numeric', 'min:0'],
            'price_date' => ['required', 'date'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'broker_fish_type_id.required' => 'Please select a fish type to price.',
            'broker_fish_type_id.exists' => 'The selected fish type assignment is invalid.',
            'price.required' => 'Please enter the fish price.',
            'price.numeric' => 'Fish price must be a valid number.',
            'price.min' => 'Fish price must be at least 0.',
            'default_cost_price.numeric' => 'Cost price must be a valid number.',
            'default_cost_price.min' => 'Cost price must be at least 0.',
            'price_date.required' => 'Please select the effective date.',
            'price_date.date' => 'Please enter a valid effective date.',
        ];
    }
}
