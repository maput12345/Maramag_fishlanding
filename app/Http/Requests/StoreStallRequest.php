<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stall_number' => ['required', 'string', 'max:50', Rule::unique('stalls', 'stall_number')],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
