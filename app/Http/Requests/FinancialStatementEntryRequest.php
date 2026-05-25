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
            'entry_form_mode' => ['required', 'string', Rule::in(['expenses', 'loss_on_sale'])],
            'expenses' => ['nullable', 'array'],
            'expenses.*.category' => [
                'required_with:expenses.*.amount',
                'string',
                Rule::in(array_keys(FinancialStatementEntry::expenseCategoryOptions())),
            ],
            'expenses.*.description' => ['nullable', 'string', 'max:255'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0.01'],
            'loss_description' => ['nullable', 'string', 'max:255'],
            'loss_amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $mode = $this->input('entry_form_mode', 'expenses');
            $hasExpense = false;

            foreach ((array) $this->input('expenses', []) as $index => $expense) {
                if ($mode !== 'expenses') {
                    continue;
                }

                $amount = $expense['amount'] ?? null;
                $category = $expense['category'] ?? null;

                if ($amount === null || $amount === '') {
                    continue;
                }

                $hasExpense = true;

                if ($category === FinancialStatementEntry::EXPENSE_CATEGORY_OTHER && empty($expense['description'])) {
                    $validator->errors()->add("expenses.$index.description", 'Please describe the other expense.');
                }
            }

            $hasLoss = $mode === 'loss_on_sale' && $this->filled('loss_amount');

            if ($hasLoss && !$this->filled('loss_description')) {
                $validator->errors()->add('loss_description', 'Please describe the loss on sale.');
            }

            if ($mode === 'expenses' && !$hasExpense) {
                $validator->errors()->add('expenses', 'Please enter at least one expense amount or loss on sale.');
            } elseif ($mode === 'loss_on_sale' && !$hasLoss) {
                $validator->errors()->add('loss_amount', 'Please enter the loss on sale amount.');
            }
        });
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
            'entry_form_mode.required' => 'Please choose the entry type.',
            'entry_form_mode.in' => 'The selected entry type is invalid.',
            'expenses.*.category.required_with' => 'Each expense amount needs a category.',
            'expenses.*.category.in' => 'One of the selected expense categories is invalid.',
            'expenses.*.description.max' => 'The expense description may not be greater than 255 characters.',
            'expenses.*.amount.numeric' => 'Each expense amount must be a valid number.',
            'expenses.*.amount.min' => 'Each expense amount must be at least 0.01.',
            'loss_description.max' => 'The loss description may not be greater than 255 characters.',
            'loss_amount.numeric' => 'The loss amount must be a valid number.',
            'loss_amount.min' => 'The loss amount must be at least 0.01.',
        ];
    }
}
