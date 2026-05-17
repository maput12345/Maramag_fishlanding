@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'User Management']
    ];

    $adminTabQuery = array_merge(request()->except(['tab', 'page']), ['tab' => 'admins']);
    $brokerTabQuery = array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'brokers']);
    $cashierTabQuery = array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'cashiers']);
    $applicantTabQuery = array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'applicants']);
    $currentResults = match ($tab) {
        'brokers' => $brokers->count(),
        'cashiers' => $cashiers->count(),
        'applicants' => $applicants->count(),
        default => $admins->count(),
    };
@endphp

@section('content')
<style>
    .add-user-modal {
        inset: 0;
        align-items: flex-start;
    }

    .add-user-panel {
        align-self: flex-start;
        height: auto;
        max-height: calc(100vh - 8rem);
        overflow: hidden;
        transition: margin 200ms ease-out, max-width 200ms ease-out;
        will-change: margin, max-width;
    }

    .add-user-panel form {
        max-height: none;
    }

    .add-user-panel.is-expanded form {
        max-height: calc(100vh - 13rem);
        overflow-y: auto;
    }

    @media (min-width: 768px) {
        .add-user-panel.role-cashier [data-modal-order="email"] {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="w-full">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="app-page-header">
                        <div class="app-page-header__content">
                            <h1 class="app-page-title">User Management</h1>
                        </div>
                        <button type="button" class="app-button app-button--primary" data-add-user-open>
                            <x-heroicon-o-plus class="w-5 h-5" />
                            Add User
                        </button>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="bg-white rounded-xl shadow-lg mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-8 px-4 md:px-6" aria-label="Tabs">
                            <a href="{{ route('admin.users.index', $adminTabQuery) }}"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $tab === 'admins' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-users class="w-5 h-5" />
                                    <span>LEEO Team ({{ $count['totalAdmins'] }})</span>
                                </div>
                            </a>
                            <a href="{{ route('admin.users.index', $brokerTabQuery) }}"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $tab === 'brokers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-user-group class="w-5 h-5" />
                                    <span>Brokers ({{ $count['totalBrokers'] }})</span>
                                </div>
                            </a>
                            <a href="{{ route('admin.users.index', $cashierTabQuery) }}"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $tab === 'cashiers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-calculator class="w-5 h-5" />
                                    <span>Cashiers ({{ $count['totalCashiers'] }})</span>
                                </div>
                            </a>
                            <a href="{{ route('admin.users.index', $applicantTabQuery) }}"
                               class="whitespace-nowrap py-3 md:py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $tab === 'applicants' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-archive-box class="w-5 h-5" />
                                    <span>Applicant Archive ({{ $count['totalApplicants'] }})</span>
                                </div>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-lg mb-6">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="p-4 md:p-6 space-y-5">
                        <input type="hidden" name="tab" value="{{ $tab }}">

                        <div class="user-filter-layout {{ $tab === 'brokers' ? 'user-filter-layout--brokers' : '' }}">
                            <div class="search-field">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text"
                                       name="search"
                                       id="search"
                                       value="{{ $search }}"
                                       placeholder="{{ $tab === 'admins' ? 'Search by name, email, position, or contact number' : ($tab === 'applicants' ? 'Search by applicant name, email, or status' : ($tab === 'cashiers' ? 'Search by cashier, email, or assigned broker' : 'Search by name, email, stall, address, or contact number')) }}"
                                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="status-field">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status"
                                        id="status"
                                        class="app-select block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="deactivated" {{ $status === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                                </select>
                            </div>

                            @if($tab === 'admins')
                                <div class="role-field">
                                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                    <select name="role"
                                            id="role"
                                            class="app-select block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                        <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All roles</option>
                                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                </div>
                            @endif

                            <div class="buttons-field filter-action-group justify-end">
                                <a href="{{ route('admin.users.index', ['tab' => $tab]) }}"
                                   class="btn-clear">
                                    Reset
                                </a>
                                <button type="submit"
                                        class="btn-search">
                                    Search
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    @if($tab === 'admins')
                        @include('admin.users.admin-list', ['admins' => $admins, 'count' => $count])
                    @elseif($tab === 'brokers')
                        @include('admin.users.broker-list', ['brokers' => $brokers, 'count' => $count])
                    @elseif($tab === 'cashiers')
                        @include('admin.users.cashier-list', ['cashiers' => $cashiers, 'count' => $count])
                    @else
                        @include('admin.users.applicant-list', ['applicants' => $applicants, 'count' => $count])
                    @endif
                </div>
            </div>

<div class="add-user-modal fixed inset-0 z-50 {{ $errors->any() ? '' : 'hidden' }} overflow-y-auto bg-slate-900/50 px-4 py-6 backdrop-blur-sm"
     data-add-user-modal
     role="dialog"
     aria-modal="true"
     aria-labelledby="add-user-modal-title">
    <div class="add-user-panel w-full max-w-2xl rounded-xl bg-white shadow-2xl"
         data-add-user-panel>
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">User Management</p>
                <h2 id="add-user-modal-title" class="text-xl font-bold text-slate-900">Add User</h2>
            </div>
            <button type="button"
                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    data-add-user-close
                    aria-label="Close add user modal">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="px-5 py-4" data-user-confirm="create-user" novalidate>
            @csrf
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label for="modal_role" class="block text-sm font-medium text-gray-700">User Role <span class="text-red-500">*</span></label>
                    <select name="role" id="modal_role" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('role') border-red-500 @enderror" data-modal-role-select>
                        <option value="">Select a role</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="broker" {{ old('role') === 'broker' ? 'selected' : '' }}>Broker</option>
                        <option value="cashier" {{ old('role') === 'cashier' ? 'selected' : '' }}>Cashier Staff</option>
                    </select>
                    @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div data-modal-role-field="cashier">
                    <label for="modal_broker_id" class="block text-sm font-medium text-gray-700">Assigned Broker <span class="text-red-500">*</span></label>
                    <select name="broker_id" id="modal_broker_id" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('broker_id') border-red-500 @enderror">
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
                    @error('broker_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-3 {{ old('role') ? '' : 'hidden' }}" data-modal-user-details>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label for="modal_first_name" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="modal_first_name" value="{{ old('first_name') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('first_name') border-red-500 @enderror">
                        @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="modal_middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text" name="middle_name" id="modal_middle_name" value="{{ old('middle_name') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('middle_name') border-red-500 @enderror">
                        @error('middle_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="modal_last_name" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="modal_last_name" value="{{ old('last_name') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('last_name') border-red-500 @enderror">
                        @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="modal_contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="text" name="contact_number" id="modal_contact_number" value="{{ old('contact_number') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('contact_number') border-red-500 @enderror">
                        @error('contact_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div data-modal-role-field="admin staff" data-modal-order="position">
                        <label for="modal_position" class="block text-sm font-medium text-gray-700">Position</label>
                        <input type="text" name="position" id="modal_position" value="{{ old('position') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('position') border-red-500 @enderror">
                        @error('position')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div data-modal-order="email">
                        <label for="modal_email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="modal_email" value="{{ old('email') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div data-modal-order="password">
                        <label for="modal_password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="modal_password" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div data-modal-order="password-confirmation">
                        <label for="modal_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" id="modal_password_confirmation" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    </div>

                <div data-modal-role-field="broker">
                    <label for="modal_stall_name" class="block text-sm font-medium text-gray-700">Stall Name</label>
                    <input type="text" name="stall_name" id="modal_stall_name" value="{{ old('stall_name') }}" class="mt-1 block h-10 w-full rounded-md border border-gray-300 px-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('stall_name') border-red-500 @enderror">
                    @error('stall_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-3" data-modal-role-field="broker">
                <label for="modal_address" class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" id="modal_address" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            </div>

            <div class="mt-5 flex justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" class="app-button app-button--secondary" data-add-user-close>Cancel</button>
                <button type="submit" class="app-button app-button--primary disabled:cursor-not-allowed disabled:opacity-50" data-add-user-submit>Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Users page specific JS -->
<script src="{{ asset('js/user.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-add-user-modal]');
    const openButton = document.querySelector('[data-add-user-open]');
    const closeButtons = document.querySelectorAll('[data-add-user-close]');
    const roleSelect = document.querySelector('[data-modal-role-select]');
    const roleFields = document.querySelectorAll('[data-modal-role-field]');
    const userDetails = document.querySelector('[data-modal-user-details]');
    const modalPanel = document.querySelector('[data-add-user-panel]');
    const submitButton = document.querySelector('[data-add-user-submit]');
    const orderedFields = document.querySelectorAll('[data-modal-order]');

    const updateModalAfterLayoutChange = () => {
        updateModalFrame();
        window.setTimeout(updateModalFrame, 100);
        window.setTimeout(updateModalFrame, 180);
        window.setTimeout(updateModalFrame, 230);
    };

    const updateModalFrame = () => {
        if (!modalPanel) {
            return;
        }

        if (window.innerWidth < 768) {
            modalPanel.style.marginLeft = 'auto';
            modalPanel.style.marginRight = 'auto';
            modalPanel.style.marginTop = '0px';
            modalPanel.style.maxWidth = '42rem';
            return;
        }

        const mainShell = document.querySelector('[data-admin-main]');
        const topbar = document.querySelector('.app-topbar');
        const mainRect = mainShell?.getBoundingClientRect();
        const topbarRect = topbar?.getBoundingClientRect();

        const left = Math.max(0, mainRect?.left ?? 0);
        const top = Math.max(16, (topbarRect?.bottom ?? 0) + 16);
        const availableWidth = Math.max(320, window.innerWidth - left - 16);
        const panelWidth = Math.min(672, availableWidth - 32);
        const sideMargin = Math.max(16, (availableWidth - panelWidth) / 2);

        modalPanel.style.maxWidth = `${panelWidth}px`;
        modalPanel.style.marginLeft = `${left + sideMargin}px`;
        modalPanel.style.marginRight = '16px';
        modalPanel.style.marginTop = `${top}px`;
    };

    const openModal = () => {
        updateModalFrame();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    const syncRoleFields = () => {
        const selectedRole = roleSelect?.value || '';

        if (userDetails) {
            const hasRole = Boolean(selectedRole);
            userDetails.classList.toggle('hidden', !hasRole);
            userDetails.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !hasRole;
            });
        }

        modalPanel?.classList.toggle('is-expanded', Boolean(selectedRole));
        modalPanel?.classList.toggle('role-cashier', selectedRole === 'cashier');
        if (submitButton) {
            submitButton.disabled = !selectedRole;
        }

        orderedFields.forEach((field) => {
            field.classList.remove('hidden');

            if (selectedRole === 'cashier') {
                if (field.dataset.modalOrder === 'position') {
                    field.classList.add('hidden');
                }
            }
        });

        roleFields.forEach((field) => {
            const allowedRoles = (field.dataset.modalRoleField || '').split(/\s+/).filter(Boolean);
            const shouldShow = Boolean(selectedRole) && allowedRoles.includes(selectedRole);
            field.classList.toggle('hidden', !shouldShow);
            field.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !shouldShow;
            });
        });
    };

    openButton?.addEventListener('click', openModal);
    window.addEventListener('admin-sidebar-toggled', updateModalAfterLayoutChange);
    window.addEventListener('resize', updateModalFrame);
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
    roleSelect?.addEventListener('change', syncRoleFields);
    syncRoleFields();

    if (modal && !modal.classList.contains('hidden')) {
        updateModalFrame();
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
});
</script>
@endsection
