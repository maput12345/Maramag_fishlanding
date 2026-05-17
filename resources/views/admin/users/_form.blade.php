@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'User Management', 'url' => route('admin.users.index')],
        ['title' => $title ?? 'User Form']
    ];
@endphp

@section('content')
<div class="w-full max-w-5xl mx-auto">
                <!-- Page Header -->
                <div class="mb-3">
                    <div class="app-page-header !items-start">
                        <div class="app-page-header__content">
                            <p class="app-page-kicker">Administration</p>
                            <h1 class="app-page-title">{{ $title ?? 'User Form' }}</h1>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-xl shadow-lg p-3 md:p-4">
                    <form method="POST" action="{{ $action }}" data-user-confirm="{{ isset($user) && $user->id ? 'update-user' : 'create-user' }}">
                        @csrf
                        @if(isset($user) && $user->id)
                            @method('PUT')
                        @endif

                        <div class="space-y-3">
                            <!-- Name -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" id="first_name"
                                           value="{{ old('first_name', $profile->first_name ?? '') }}"
                                           class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('first_name') border-red-500 @enderror"
                                           required>
                                    @error('first_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name"
                                           value="{{ old('middle_name', $profile->middle_name ?? '') }}"
                                           class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('middle_name') border-red-500 @enderror">
                                    @error('middle_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name', $profile->last_name ?? '') }}"
                                           class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('last_name') border-red-500 @enderror"
                                           required>
                                    @error('last_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email"
                                       value="{{ old('email', $user->email ?? '') }}"
                                       class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                                       required>
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Password Section -->
                            <div>
                                @if(isset($user) && $user->id)
                                    <!-- Edit Mode: Password Change Checkbox -->
                                    <div x-data="{ changePassword: {{ old('change_password') ? 'true' : 'false' }} }">
                                    <div class="mb-4">
                                        <label class="flex items-center">
                                                <input type="checkbox" id="change_password" name="change_password" value="1" x-model="changePassword"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700">Change Password</span>
                                        </label>
                                    </div>

                                        <!-- Password Fields (hidden by default, shown when checkbox is checked) -->
                                        <div id="password_fields" class="space-y-4" x-show="changePassword" x-cloak style="display: none;">
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                                <input type="password" name="password" id="password" :disabled="!changePassword"
                                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                                            @error('password')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                                <input type="password" name="password_confirmation" id="password_confirmation" :disabled="!changePassword"
                                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password_confirmation') border-red-500 @enderror">
                                            @error('password_confirmation')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Create Mode: Required Password Fields -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                                        <input type="password" name="password" id="password"
                                               class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                                               required>
                                        @error('password')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password_confirmation') border-red-500 @enderror"
                                               required>
                                    @error('password_confirmation')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Role (only for create) -->
                            @if(!isset($user) || !$user->id)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">User Role <span class="text-red-500">*</span></label>
                                    <select name="role" id="role"
                                            class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror"
                                            required>
                                        <option value="">Select a role</option>
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                                        <option value="broker" {{ old('role') == 'broker' ? 'selected' : '' }}>Broker</option>
                                        <option value="cashier" {{ old('role') == 'cashier' ? 'selected' : '' }}>Cashier Staff</option>
                                    </select>
                                    @error('role')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div data-role-field="cashier" class="{{ old('role') === 'cashier' ? '' : 'hidden' }}">
                                    <label for="broker_id" class="block text-sm font-medium text-gray-700">Assigned Broker <span class="text-red-500">*</span></label>
                                    <select name="broker_id" id="broker_id"
                                            class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('broker_id') border-red-500 @enderror">
                                        <option value="">Select broker/stall</option>
                                        @foreach(($brokersForAssignment ?? collect()) as $brokerOption)
                                            @php
                                                $stallLabel = $brokerOption->stall_name
                                                    ?? ($brokerOption->stall?->stall_number ? 'Stall ' . $brokerOption->stall->stall_number : null)
                                                    ?? 'No stall assigned';
                                            @endphp
                                            <option value="{{ $brokerOption->id }}" {{ (string) old('broker_id') === (string) $brokerOption->id ? 'selected' : '' }}>
                                                {{ $stallLabel }} - {{ $brokerOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Cashier staff can only sell under this broker account.</p>
                                    @error('broker_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div data-role-field="admin staff">
                                    <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
                                    <input type="text" name="position" id="position"
                                           value="{{ old('position', $profile->position ?? '') }}"
                                           class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('position') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500">Use this for LEEO admin and staff accounts.</p>
                                    @error('position')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div data-role-field="admin staff broker cashier">
                                    <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                                    <input type="text" name="contact_number" id="contact_number"
                                           value="{{ old('contact_number', $profile->contact_number ?? '') }}"
                                           class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('contact_number') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500" data-contact-help>Use this for contact details.</p>
                                    @error('contact_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div data-role-field="broker">
                                <label for="stall_name" class="block text-sm font-medium text-gray-700">Stall Name</label>
                                <input type="text" name="stall_name" id="stall_name"
                                       value="{{ old('stall_name', $profile->stall_name ?? '') }}"
                                       class="mt-1 block h-10 w-full border border-gray-300 rounded-md px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('stall_name') border-red-500 @enderror">
                                <p class="mt-1 text-xs text-gray-500">Use this for broker accounts.</p>
                                @error('stall_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Address -->
                            <div data-role-field="broker">
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea name="address" id="address" rows="2"
                                          class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror">{{ old('address', $profile->address ?? '') }}</textarea>
                                @error('address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-3 pt-1">
                                <a href="{{ route('admin.users.index') }}"
                                   class="app-button app-button--secondary">
                                    Cancel
                                </a>
                                <button type="submit"
                                        class="app-button app-button--primary">
                                    {{ isset($user) && $user->id ? 'Update User' : 'Create User' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.querySelector('#role');
    const roleFields = document.querySelectorAll('[data-role-field]');
    const contactHelp = document.querySelector('[data-contact-help]');

    if (roleSelect && roleFields.length) {
        const syncRoleFields = () => {
            const selectedRole = roleSelect.value;

            roleFields.forEach((field) => {
                const allowedRoles = (field.dataset.roleField || '').split(/\s+/).filter(Boolean);
                const shouldShow = !selectedRole || allowedRoles.includes(selectedRole);

                field.classList.toggle('hidden', !shouldShow);
                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !shouldShow;
                });
            });

            if (contactHelp) {
                contactHelp.textContent = selectedRole === 'cashier'
                    ? 'Use this for the cashier contact number.'
                    : selectedRole === 'broker'
                        ? 'Use this for the broker contact number.'
                        : 'Use this for LEEO employee contact details.';
            }
        };

        roleSelect.addEventListener('change', syncRoleFields);
        syncRoleFields();
    }

    document.querySelectorAll('form[data-user-confirm]').forEach((form) => {
        let confirmed = false;

        form.addEventListener('submit', (event) => {
            if (confirmed) {
                return;
            }

            event.preventDefault();

            const mode = form.dataset.userConfirm;
            const firstName = form.querySelector('[name="first_name"]')?.value?.trim() || '';
            const lastName = form.querySelector('[name="last_name"]')?.value?.trim() || '';
            const email = form.querySelector('[name="email"]')?.value?.trim() || '';
            const roleSelect = form.querySelector('[name="role"]');
            const role = roleSelect?.selectedOptions?.[0]?.textContent?.trim() || 'selected role';
            const displayName = [firstName, lastName].filter(Boolean).join(' ') || email || 'this user';
            const isCreate = mode === 'create-user';
            const config = isCreate
                ? {
                    title: 'Create this user account?',
                    text: `This will create ${displayName} as ${role}. Please confirm the email address before saving.`,
                    confirmButtonText: 'Yes, create user',
                    confirmButtonColor: '#059669',
                    icon: 'question',
                }
                : {
                    title: 'Update this user account?',
                    text: `This will save changes for ${displayName}.`,
                    confirmButtonText: 'Yes, update user',
                    confirmButtonColor: '#2563eb',
                    icon: 'question',
                };

            if (!window.Swal) {
                if (window.confirm(config.title)) {
                    confirmed = true;
                    form.requestSubmit();
                }

                return;
            }

            window.Swal.fire({
                title: config.title,
                text: config.text,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonText: config.confirmButtonText,
                cancelButtonText: 'Review again',
                confirmButtonColor: config.confirmButtonColor,
                cancelButtonColor: '#64748b',
                focusCancel: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmed = true;
                    form.requestSubmit();
                }
            });
        });
    });
});
</script>
@endsection
