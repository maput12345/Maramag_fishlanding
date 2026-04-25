<?php

namespace App\Http\Requests;

use App\Models\FinancialStatementEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialStatementEntryRequest extends FormRequest
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
            'statement_date' => ['required', 'date'],
            'entry_type' => [
                'required',
                'string',
                Rule::in(array_keys(FinancialStatementEntry::typeOptions())),
            ],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
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
            'statement_date.required' => 'Please select the statement date.',
            'statement_date.date' => 'Please enter a valid statement date.',
            'entry_type.required' => 'Please choose which financial statement line this entry belongs to.',
            'entry_type.in' => 'The selected financial statement line is invalid.',
            'description.required' => 'Please describe the daily adjustment.',
            'description.max' => 'The description may not be greater than 255 characters.',
            'amount.required' => 'Please enter the amount.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be at least 0.01.',
        ];
    }
}
