<?php

namespace App\Http\Requests;

use App\Models\ApplicationOpening;
use App\Models\Stall;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stall_id' => ['required', 'exists:stalls,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $stallId = (int) $this->input('stall_id');

            if (!$stallId) {
                return;
            }

            $stall = Stall::find($stallId);

            if ($stall && $stall->stall_status === 'Occupied') {
                $validator->errors()->add('stall_id', 'Occupied stalls cannot be opened for new applications.');
            }

            $hasActiveOpening = ApplicationOpening::where('stall_id', $stallId)
                ->whereIn('opening_status', ['Open', 'Closed'])
                ->exists();

            if ($hasActiveOpening) {
                $validator->errors()->add('stall_id', 'This stall already has an active application opening.');
            }
        });
    }
}
