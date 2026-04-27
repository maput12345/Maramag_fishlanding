@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'User Management']
    ];

    $adminTabQuery = array_merge(request()->except(['tab', 'page']), ['tab' => 'admins']);
    $brokerTabQuery = array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'brokers']);
    $applicantTabQuery = array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'applicants']);
    $currentResults = match ($tab) {
        'brokers' => $brokers->count(),
        'applicants' => $applicants->count(),
        default => $admins->count(),
    };
@endphp

@section('content')
<div class="w-full">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="app-page-header">
                        <div class="app-page-header__content">
                            <p class="app-page-kicker">Administration</p>
                            <h1 class="app-page-title">User Management</h1>
                            <p class="app-page-description">Manage LEEO accounts and broker profiles from one consistent workspace.</p>
                        </div>
                        <a href="{{ route('admin.users.create') }}" class="app-button app-button--primary">
                            <x-heroicon-o-plus class="w-5 h-5" />
                            Add User
                        </a>
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
                                       placeholder="{{ $tab === 'admins' ? 'Search by name, email, position, or contact number' : ($tab === 'applicants' ? 'Search by applicant name, email, or status' : 'Search by name, email, stall, address, or contact number') }}"
                                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="status-field">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status"
                                        id="status"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
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
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                        <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All roles</option>
                                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                </div>
                            @endif

                            <div class="buttons-field flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.index', ['tab' => $tab]) }}"
                                   class="app-button app-button--secondary">
                                    Reset
                                </a>
                                <button type="submit"
                                        class="app-button app-button--primary">
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
                    @else
                        @include('admin.users.applicant-list', ['applicants' => $applicants, 'count' => $count])
                    @endif
                </div>
            </div>

<!-- Users page specific JS -->
<script src="{{ asset('js/user.js') }}" defer></script>
@endsection
