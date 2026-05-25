<?php

namespace App\Http\Requests;

use App\Constants\FishBoxStatusConstant;
use App\Models\Broker;
use App\Models\FishBox;
use App\Models\FishType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class BulkRestockFishBoxesRequest extends FormRequest
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
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());

        return [
            'fish_box_ids' => ['required', 'array', 'min:1'],
            'fish_box_ids.*' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($brokerId) {
                    $isEligible = FishBox::query()
                        ->whereKey($value)
                        ->where('broker_id', $brokerId)
                        ->whereIn('box_status', [
                            FishBoxStatusConstant::UNASSIGNED,
                            FishBoxStatusConstant::RETURNED,
                        ])
                        ->exists();

                    if (!$isEligible) {
                        $fail('Only unassigned or returned fish boxes can be selected for daily restocking.');
                    }
                },
            ],
            'fish_type_id' => [
                'required',
                'exists:FishType,id',
                function ($attribute, $value, $fail) use ($brokerId) {
                    $isAssigned = FishType::whereKey($value)
                        ->whereHas('brokers', function ($query) use ($brokerId) {
                            $query->where('Broker.id', $brokerId);
                        })
                        ->exists();

                    if (!$isAssigned) {
                        $fail('The selected fish type is not assigned to your account.');
                    }
                },
            ],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
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
            'fish_box_ids.required' => 'Please select at least one fish box to restock.',
            'fish_box_ids.array' => 'Please select valid fish boxes to restock.',
            'fish_box_ids.min' => 'Please select at least one fish box to restock.',
            'fish_type_id.required' => 'Please select the fish name for this daily restock.',
            'fish_type_id.exists' => 'The selected fish type is invalid.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'cost_price.min' => 'Cost price must be at least 0.',
        ];
    }
}
