<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $passwordOption = $this->input('password_option', 'keep');

        $rules = [];

        if ($user && ($user->isAdmin() || $user->isStaff() || $user->isBroker() || $user->isApplicant())) {
            $rules = [
                'first_name' => ['required', 'string', 'max:255', 'min:2'],
                'middle_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255', 'min:1'],
                'suffix' => ['nullable', 'string', 'max:50'],
                'contact_number' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string', 'max:500'],
            ];
        }

        // Add stall_name validation for brokers only
        if ($user && $user->isBroker()) {
            $rules['stall_name'] = ['required', 'string', 'max:255'];
        }

        // Add password validation rules only if changing password
        if ($passwordOption === 'change') {
            $rules['current_password'] = ['required'];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'The first name field is required.',
            'first_name.max' => 'The first name may not be greater than 255 characters.',
            'last_name.required' => 'The last name field is required.',
            'last_name.max' => 'The last name may not be greater than 255 characters.',
            'address.max' => 'The address may not be greater than 500 characters.',
            'stall_name.required' => 'The stall name field is required.',
            'stall_name.max' => 'The stall name may not be greater than 255 characters.',
            'current_password.required' => 'Current password is required when changing password.',
            'password.required' => 'New password is required when changing password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $passwordOption = $this->input('password_option', 'keep');

            if ($passwordOption === 'change') {
                $user = Auth::user();
                if (!Hash::check($this->current_password, $user->password)) {
                    $validator->errors()->add('current_password', 'The provided password does not match your current password.');
                }
            }
        });
    }
}
