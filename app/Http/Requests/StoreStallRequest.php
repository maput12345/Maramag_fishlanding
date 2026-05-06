<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'length_meters' => ['required', 'numeric', 'gt:0'],
            'width_meters' => ['required', 'numeric', 'gt:0'],
            'address' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'stall_images' => ['nullable', 'array', 'max:6'],
            'stall_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
