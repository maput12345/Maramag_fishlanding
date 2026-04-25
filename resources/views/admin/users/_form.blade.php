@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'User Management', 'url' => route('admin.users.index')],
        ['title' => $title ?? 'User Form']
    ];
@endphp

@section('content')
<div class="w-full max-w-2xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="app-page-header">
                        <div class="app-page-header__content">
                            <p class="app-page-kicker">Administration</p>
                            <h1 class="app-page-title">{{ $title ?? 'User Form' }}</h1>
                            <p class="app-page-description">{{ $description ?? 'Manage user information.' }}</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="app-button app-button--secondary">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Users
                        </a>
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @if(isset($user) && $user->id)
                            @method('PUT')
                        @endif

                        <div class="space-y-6">
                            <!-- Name -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" id="first_name"
                                           value="{{ old('first_name', $profile->first_name ?? '') }}"
                                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('first_name') border-red-500 @enderror"
                                           required>
                                    @error('first_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name"
                                           value="{{ old('middle_name', $profile->middle_name ?? '') }}"
                                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('middle_name') border-red-500 @enderror">
                                    @error('middle_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name', $profile->last_name ?? '') }}"
                                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('last_name') border-red-500 @enderror"
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
                                       class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
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
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                                        <input type="password" name="password" id="password"
                                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                                               required>
                                        @error('password')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password_confirmation') border-red-500 @enderror"
                                               required>
                                        @error('password_confirmation')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            </div>

                            <!-- Role (only for create) -->
                            @if(!isset($user) || !$user->id)
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">User Role <span class="text-red-500">*</span></label>
                                    <select name="role" id="role"
                                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror"
                                            required>
                                        <option value="">Select a role</option>
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                                        <option value="broker" {{ old('role') == 'broker' ? 'selected' : '' }}>Broker</option>
                                    </select>
                                    @error('role')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
                                    <input type="text" name="position" id="position"
                                           value="{{ old('position', $profile->position ?? '') }}"
                                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('position') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500">Use this for LEEO admin and staff accounts.</p>
                                    @error('position')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                                    <input type="text" name="contact_number" id="contact_number"
                                           value="{{ old('contact_number', $profile->contact_number ?? '') }}"
                                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('contact_number') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500">Use this for LEEO employee contact details.</p>
                                    @error('contact_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="stall_name" class="block text-sm font-medium text-gray-700">Stall Name</label>
                                <input type="text" name="stall_name" id="stall_name"
                                       value="{{ old('stall_name', $profile->stall_name ?? '') }}"
                                       class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('stall_name') border-red-500 @enderror">
                                <p class="mt-1 text-xs text-gray-500">Use this for broker accounts.</p>
                                @error('stall_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea name="address" id="address" rows="3"
                                          class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror">{{ old('address', $profile->address ?? '') }}</textarea>
                                @error('address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-3">
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
@endsection
