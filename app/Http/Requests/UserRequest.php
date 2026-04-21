<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // Get the user ID and ensure it's an integer
        $userId = $this->route('id');
        $userId = is_numeric($userId) ? (int) $userId : $userId;

        $emailRules = ['required', 'string', 'email', 'max:255'];

        // Only add unique rule if we have a user ID (update mode)
        if ($userId) {
            // Try different approaches for the unique rule
            $emailRules[] = Rule::unique('users', 'email')->ignore($userId, 'id');
        } else {
            $emailRules[] = 'unique:users,email';
        }

        // Password validation rules
        $passwordRules = [];

        if ($this->isMethod('post')) {
            // Create mode - password is always required
            $passwordRules = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            // Update mode - check if change_password checkbox is checked
            if ($this->has('change_password') && $this->input('change_password')) {
                // If checkbox is checked, password is required
                $passwordRules = ['required', 'string', 'min:8', 'confirmed'];
            } else {
                // If checkbox is not checked, password is nullable
                $passwordRules = ['nullable', 'string', 'min:8'];
            }
        }

        return [
            'first_name' => ['required', 'string', 'max:255', 'min:2'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'min:1'],
            'email' => $emailRules,
            'password' => $passwordRules,
            'address' => ['nullable', 'string', 'max:500'],
            'stall_name' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'role' => $this->isMethod('post')
                ? ['required', 'string', Rule::in(['admin', 'staff', 'broker'])]
                : ['prohibited'],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'first_name.required' => 'The first name field is required.',
            'first_name.min' => 'The first name must be at least 2 characters.',
            'last_name.required' => 'The last name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.required' => 'Please select a user role.',
            'role.in' => 'The selected role is invalid.',
            'role.prohibited' => 'Role cannot be changed after creation.',
            'address.max' => 'The address may not be greater than 500 characters.',
            'stall_name.max' => 'The stall name may not be greater than 255 characters.',
            'contact_number.max' => 'The contact number may not be greater than 50 characters.',
            'position.max' => 'The position may not be greater than 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'first_name' => 'first name',
            'middle_name' => 'middle name',
            'last_name' => 'last name',
            'email' => 'email address',
            'password' => 'password',
            'address' => 'address',
            'stall_name' => 'stall name',
            'contact_number' => 'contact number',
            'position' => 'position',
            'role' => 'user role',
        ];
    }
}
