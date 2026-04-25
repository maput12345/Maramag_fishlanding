<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bidding_date' => ['required', 'date'],
            'bidding_location' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $opening = $this->route('opening');

            if (!$opening || !$this->filled('bidding_date')) {
                return;
            }

            if ($opening->start_date && $this->date('bidding_date')->lt($opening->start_date)) {
                $validator->errors()->add(
                    'bidding_date',
                    'The bidding date cannot be earlier than the application opening start date.'
                );
            }
        });
    }
}
