<?php

namespace App\Http\Requests;

use App\Models\Broker;
use App\Models\SalesTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class SalesPaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_number' => trim((string) $this->input('reference_number', '')),
        ]);
    }

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
            'sales_id' => 'required|exists:SalesTransaction,id',
            'paid_amount' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $sale = $this->getAuthorizedSale();

                    if (!$sale) {
                        return;
                    }

                    if ((float) $value > (float) $sale->remaining_amount) {
                        $fail('Payment amount cannot exceed the remaining balance of ₱' . number_format((float) $sale->remaining_amount, 2) . '.');
                    }
                },
            ],
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:255',
            'reference_number' => 'nullable|required_if:payment_method,GCash,Bank Transfer|string|max:100',
        ];
    }

    /**
     * Add broker ownership validation after the base rule checks.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->filled('sales_id')) {
                return;
            }

            $brokerId = $this->resolveCurrentBrokerId();

            if (!$brokerId) {
                $validator->errors()->add('sales_id', 'Only broker accounts with an active broker profile can record payments.');
                return;
            }

            if (!$this->saleExists()) {
                return;
            }

            if (!$this->getAuthorizedSale()) {
                $validator->errors()->add('sales_id', 'The selected sale does not belong to your broker account.');
            }
        });
    }

    /**
     * Resolve the current broker profile for the authenticated user.
     */
    private function resolveCurrentBrokerId(): ?int
    {
        $userId = Auth::id();

        return $userId ? Broker::getBrokerIdByUserId($userId) : null;
    }

    /**
     * Check whether the submitted sale exists at all.
     */
    private function saleExists(): bool
    {
        $salesId = $this->input('sales_id');

        if (!$salesId || is_array($salesId)) {
            return false;
        }

        return SalesTransaction::query()->whereKey($salesId)->exists();
    }

    /**
     * Get the submitted sale only when it belongs to the current broker.
     */
    private function getAuthorizedSale(): ?SalesTransaction
    {
        $salesId = $this->input('sales_id');
        $brokerId = $this->resolveCurrentBrokerId();

        if (!$salesId || is_array($salesId) || !$brokerId) {
            return null;
        }

        return SalesTransaction::query()
            ->whereKey($salesId)
            ->where('broker_id', $brokerId)
            ->first();
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sales_id.required' => 'Please select a sale.',
            'sales_id.exists' => 'The selected sale is invalid.',
            'paid_amount.required' => 'Please enter the paid amount.',
            'paid_amount.numeric' => 'Paid amount must be a valid number.',
            'paid_amount.min' => 'Paid amount must be at least 0.01.',
            'payment_date.required' => 'Please select a payment date.',
            'payment_date.date' => 'Please enter a valid payment date.',
            'payment_method.required' => 'Please enter the payment method.',
            'payment_method.max' => 'Payment method cannot exceed 255 characters.',
            'reference_number.required_if' => 'Please enter the reference number for GCash or bank transfer payments.',
            'reference_number.max' => 'Reference number cannot exceed 100 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sales_id' => 'sale',
            'paid_amount' => 'paid amount',
            'payment_date' => 'payment date',
            'payment_method' => 'payment method',
            'reference_number' => 'reference number',
        ];
    }
}
