<?php

namespace App\Http\Requests;

use App\Models\Broker;
use App\Models\Buyer;
use App\Models\FishBox;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class SalesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $fallbackName = trim((string) $this->input('buyer_name', ''));
        $fallbackNameParts = $fallbackName !== '' ? User::splitName($fallbackName) : [];
        $firstName = trim((string) $this->input('buyer_first_name', $fallbackNameParts['first_name'] ?? ''));
        $middleName = trim((string) $this->input('buyer_middle_name', $fallbackNameParts['middle_name'] ?? ''));
        $lastName = trim((string) $this->input('buyer_last_name', $fallbackNameParts['last_name'] ?? ''));

        if ($lastName === '' && !$this->has('buyer_first_name') && $fallbackName !== '') {
            $lastName = $firstName;
        }
        $contact = trim((string) $this->input('buyer_contact', ''));
        $buyerName = trim(collect([$firstName, $middleName, $lastName])->filter()->implode(' '));
        $initialReferenceNumber = trim((string) $this->input('initial_reference_number', ''));

        $this->merge([
            'buyer_first_name' => $firstName,
            'buyer_middle_name' => $middleName !== '' ? $middleName : null,
            'buyer_last_name' => $lastName,
            'buyer_contact' => $contact,
            'buyer_name' => $buyerName ?: trim((string) $this->input('buyer_name', '')),
            'initial_reference_number' => $initialReferenceNumber,
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
        $rules = [
            'sales_date' => 'required|date',
            'total_amount' => 'nullable|numeric|min:0',
            'buyer_first_name' => 'required|string|max:255',
            'buyer_middle_name' => 'nullable|string|max:255',
            'buyer_last_name' => 'required|string|max:255',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_contact' => ['required', 'string', 'regex:/^09\\d{9}$/'],
            'buyer_id' => 'nullable|integer|exists:Buyer,id',
            'initial_paid_amount' => 'nullable|numeric|min:0.01',
            'initial_payment_date' => 'nullable|required_with:initial_paid_amount|date',
            'initial_payment_method' => 'nullable|required_with:initial_paid_amount|string|max:255',
            'initial_reference_number' => [
                'nullable',
                \Illuminate\Validation\Rule::requiredIf(fn (): bool => $this->filled('initial_paid_amount')
                    && in_array($this->input('initial_payment_method'), ['GCash', 'Bank Transfer'], true)),
                'string',
                'max:100',
            ],
            'sales_details' => 'required|array|min:1',
            'sales_details.*.box_id' => 'nullable|array',
            'sales_details.*.box_id.*' => 'nullable|exists:FishBox,id',
            'sales_details.*.fish_type_id' => 'required|exists:FishType,id',
            'sales_details.*.item' => 'nullable|string|max:255',
            'sales_details.*.item_description' => 'nullable|string',
            'sales_details.*.unit_price' => 'nullable|numeric|min:0',
            'sales_details.*.quantity' => 'nullable|integer|min:1',
            'sales_details.*.sub_total' => 'nullable|numeric|min:0',
            'sales_details.*.discount_mode' => 'nullable|string|in:percent,amount',
            'sales_details.*.discount_value' => 'nullable|numeric|min:0',
            'sales_details.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'sales_details.*.discount' => 'nullable|numeric|min:0',
        ];

        return $rules;
    }

    /**
     * Ensure every submitted fish box belongs to the authenticated broker.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $flattenedBoxIds = collect($this->input('sales_details', []))
                ->flatMap(function ($detail): array {
                    if (!is_array($detail)) {
                        return [];
                    }

                    $boxIds = $detail['box_id'] ?? [];

                    return is_array($boxIds) ? $boxIds : [$boxIds];
                })
                ->filter(fn ($boxId): bool => $boxId !== null && $boxId !== '')
                ->map(fn ($boxId): int => (int) $boxId)
                ->values();

            collect($this->input('sales_details', []))->each(function ($detail, $index) use ($validator): void {
                if (!is_array($detail)) {
                    return;
                }

                $boxIds = collect($detail['box_id'] ?? [])
                    ->filter(fn ($boxId): bool => $boxId !== null && $boxId !== '')
                    ->values();

                if ($boxIds->isNotEmpty()) {
                    return;
                }

                $quantity = (int) ($detail['quantity'] ?? 0);

                if ($quantity < 1) {
                    $validator->errors()->add("sales_details.{$index}.quantity", 'Please enter how many boxes to auto-assign.');
                }
            });

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $brokerId = $this->resolveCurrentBrokerId();

            if (!$brokerId) {
                $validator->errors()->add('sales_details', 'Only broker accounts with an active broker profile can record sales.');
                return;
            }

            $buyerId = $this->input('buyer_id');

            if ($buyerId && !Buyer::query()->whereKey($buyerId)->where('broker_id', $brokerId)->exists()) {
                $validator->errors()->add('buyer_id', 'The selected regular customer does not belong to your broker account.');
                return;
            }

            if ($flattenedBoxIds->isEmpty()) {
                return;
            }

            if ($flattenedBoxIds->count() !== $flattenedBoxIds->unique()->count()) {
                $validator->errors()->add('sales_details', 'A fish box can only be used once per transaction.');
                return;
            }

            $boxIds = $flattenedBoxIds
                ->unique()
                ->values();

            $existingBoxCount = FishBox::query()
                ->whereIn('id', $boxIds->all())
                ->count();

            if ($existingBoxCount !== $boxIds->count()) {
                return;
            }

            $ownedBoxCount = FishBox::query()
                ->where('broker_id', $brokerId)
                ->whereIn('id', $boxIds->all())
                ->count();

            if ($ownedBoxCount !== $boxIds->count()) {
                $validator->errors()->add('sales_details', 'One or more selected fish boxes do not belong to your broker account.');
            }
        });
    }

    /**
     * Resolve the broker profile for the authenticated user.
     */
    private function resolveCurrentBrokerId(): ?int
    {
        $userId = Auth::id();

        return $userId ? Broker::getBrokerIdByUserId($userId) : null;
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sales_date.required' => 'Please select a sales date.',
            'sales_date.date' => 'Please enter a valid date.',
            'total_amount.required' => 'Please enter the total amount.',
            'total_amount.numeric' => 'Total amount must be a valid number.',
            'total_amount.min' => 'Total amount must be at least 0.',
            'buyer_first_name.required' => 'Please enter the buyer first name.',
            'buyer_first_name.max' => 'Buyer first name cannot exceed 255 characters.',
            'buyer_middle_name.max' => 'Buyer middle name cannot exceed 255 characters.',
            'buyer_last_name.required' => 'Please enter the buyer last name.',
            'buyer_last_name.max' => 'Buyer last name cannot exceed 255 characters.',
            'buyer_name.max' => 'Buyer name cannot exceed 255 characters.',
            'buyer_contact.required' => 'Please enter the buyer contact.',
            'buyer_contact.regex' => 'Buyer contact must be an 11-digit mobile number starting with 09.',
            'buyer_id.exists' => 'The selected regular customer could not be found.',
            'initial_paid_amount.numeric' => 'Initial paid amount must be a valid number.',
            'initial_paid_amount.min' => 'Initial paid amount must be greater than 0.',
            'initial_payment_date.required_with' => 'Please select the initial payment date.',
            'initial_payment_date.date' => 'Please enter a valid initial payment date.',
            'initial_payment_method.required_with' => 'Please select the initial payment method.',
            'initial_payment_method.max' => 'Initial payment method cannot exceed 255 characters.',
            'initial_reference_number.required' => 'Please enter the reference number for GCash or bank transfer payments.',
            'initial_reference_number.max' => 'Reference number cannot exceed 100 characters.',
            'sales_details.required' => 'Please add at least one sales detail.',
            'sales_details.min' => 'Please add at least one sales detail.',
            'sales_details.*.box_id.array' => 'Fish boxes must be provided as an array.',
            'sales_details.*.box_id.*.exists' => 'The selected fish box is invalid.',
            'sales_details.*.fish_type_id.required' => 'Please select a fish type.',
            'sales_details.*.fish_type_id.exists' => 'The selected fish type is invalid.',
            'sales_details.*.item.required' => 'Please enter the item name.',
            'sales_details.*.item.max' => 'Item name cannot exceed 255 characters.',
            'sales_details.*.unit_price.numeric' => 'Unit price must be a valid number.',
            'sales_details.*.unit_price.min' => 'Unit price must be at least 0.',
            'sales_details.*.quantity.integer' => 'Quantity must be a whole number.',
            'sales_details.*.quantity.min' => 'Quantity must be at least 1.',
            'sales_details.*.sub_total.numeric' => 'Sub total must be a valid number.',
            'sales_details.*.sub_total.min' => 'Sub total must be at least 0.',
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
            'sales_date' => 'sales date',
            'total_amount' => 'total amount',
            'buyer_first_name' => 'buyer first name',
            'buyer_middle_name' => 'buyer middle name',
            'buyer_last_name' => 'buyer last name',
            'buyer_name' => 'buyer name',
            'buyer_contact' => 'contact number',
            'buyer_id' => 'regular customer',
            'initial_paid_amount' => 'initial paid amount',
            'initial_payment_date' => 'initial payment date',
            'initial_payment_method' => 'initial payment method',
            'initial_reference_number' => 'reference number',
            'sales_details' => 'sales details',
            'sales_details.*.box_id' => 'fish box',
            'sales_details.*.fish_type_id' => 'fish type',
            'sales_details.*.item' => 'item',
            'sales_details.*.unit_price' => 'unit price',
            'sales_details.*.quantity' => 'quantity',
            'sales_details.*.sub_total' => 'sub total',
        ];
    }
}
