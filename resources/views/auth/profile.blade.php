<!-- Profile Modal -->
@if(request('modal') === 'profile')
    @php
        $profileUser = auth()->user();
        $canEditProfileDetails = $profileUser->isAdmin() || $profileUser->isStaff() || $profileUser->isBroker() || $profileUser->isApplicant();
    @endphp
    <x-app-modal
        title="Profile Settings"
        subtitle="Update your profile details and password options."
        :close-url="url()->current()"
        max-width="lg"
    >
        <x-slot:icon>
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
                <x-heroicon-o-user class="h-5 w-5" />
            </div>
        </x-slot:icon>

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                @if($canEditProfileDetails)
                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text"
                               id="first_name"
                               name="first_name"
                               value="{{ old('first_name', auth()->user()->getProfile()?->first_name ?? '') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('first_name') border-red-300 @enderror"
                               required>
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text"
                               id="middle_name"
                               name="middle_name"
                               value="{{ old('middle_name', auth()->user()->getProfile()?->middle_name ?? '') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('middle_name') border-red-300 @enderror">
                        @error('middle_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text"
                               id="last_name"
                               name="last_name"
                               value="{{ old('last_name', auth()->user()->getProfile()?->last_name ?? '') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('last_name') border-red-300 @enderror"
                               required>
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                        <input type="text"
                               id="suffix"
                               name="suffix"
                               value="{{ old('suffix', auth()->user()->getProfile()?->suffix ?? '') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('suffix') border-red-300 @enderror"
                               placeholder="Optional">
                        @error('suffix')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <input type="text"
                           id="contact_number"
                           name="contact_number"
                           value="{{ old('contact_number', auth()->user()->getProfile()?->contact_number ?? '') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('contact_number') border-red-300 @enderror"
                           placeholder="09xx xxx xxxx">
                    @error('contact_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address Field -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea id="address"
                              name="address"
                              rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('address') border-red-300 @enderror"
                              placeholder="Enter your address">{{ old('address', auth()->user()->getProfile()?->address ?? '') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stall Name Field (Brokers Only) -->
                @if(auth()->user()->isBroker())
                <div>
                    <label for="stall_name" class="block text-sm font-medium text-gray-700">Stall Name</label>
                    <input type="text"
                           id="stall_name"
                           name="stall_name"
                           value="{{ old('stall_name', auth()->user()->getProfile()?->stall_name ?? '') }}"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('stall_name') border-red-300 @enderror"
                           placeholder="Enter your stall name"
                           required>
                    @error('stall_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif
                @endif

                <!-- Email Field (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1 block w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-md text-sm text-gray-600">
                        {{ auth()->user()->email }}
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Email cannot be changed</p>
                </div>

                <!-- Password Update Radio Button -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Options</label>
                    <div class="space-y-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio"
                                   name="password_option"
                                   value="keep"
                                   {{ old('password_option', 'keep') === 'keep' ? 'checked' : '' }}
                                   class="password-option mr-2 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                   onchange="togglePasswordFields()">
                            <span class="text-sm text-gray-700">Keep current password</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio"
                                   name="password_option"
                                   value="change"
                                   {{ old('password_option', 'keep') === 'change' ? 'checked' : '' }}
                                   class="password-option mr-2 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                   onchange="togglePasswordFields()">
                            <span class="text-sm text-gray-700">Change password</span>
                        </label>
                    </div>
                </div>

                <!-- Current Password Field (Hidden by default) -->
                <div id="current_password_field" class="hidden">
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('current_password') border-red-300 @enderror"
                           placeholder="Enter current password">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password Field (Hidden by default) -->
                <div id="new_password_field" class="hidden">
                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-300 @enderror"
                           placeholder="Enter new password">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password Field (Hidden by default) -->
                <div id="confirm_password_field" class="hidden">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Confirm new password">
                </div>

                    <!-- Modal Footer -->
                    <div class="flex flex-col sm:flex-row items-center justify-end space-y-3 sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-100">
                        <a href="{{ url()->current() }}"
                           class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-center">
                            Cancel
                        </a>
                        <button type="submit"
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Profile
                        </button>
                    </div>
                </form>
    </x-app-modal>
@endif

<script>
function togglePasswordFields() {
    const selectedPasswordOption = document.querySelector('input[name="password_option"]:checked');
    if (!selectedPasswordOption) {
        return;
    }

    const passwordOption = selectedPasswordOption.value;
    const currentPasswordField = document.getElementById('current_password_field');
    const newPasswordField = document.getElementById('new_password_field');
    const confirmPasswordField = document.getElementById('confirm_password_field');
    const currentPassword = document.getElementById('current_password');
    const password = document.getElementById('password');
    const passwordConfirmation = document.getElementById('password_confirmation');

    if (!currentPasswordField || !newPasswordField || !confirmPasswordField || !currentPassword || !password || !passwordConfirmation) {
        return;
    }

    if (passwordOption === 'change') {
        currentPasswordField.classList.remove('hidden');
        newPasswordField.classList.remove('hidden');
        confirmPasswordField.classList.remove('hidden');

        // Make password fields required
        currentPassword.required = true;
        password.required = true;
        passwordConfirmation.required = true;
    } else {
        currentPasswordField.classList.add('hidden');
        newPasswordField.classList.add('hidden');
        confirmPasswordField.classList.add('hidden');

        // Remove required attribute and clear values
        currentPassword.required = false;
        password.required = false;
        passwordConfirmation.required = false;
        currentPassword.value = '';
        password.value = '';
        passwordConfirmation.value = '';
    }
}

// Initialize password fields on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePasswordFields();
});
</script>
