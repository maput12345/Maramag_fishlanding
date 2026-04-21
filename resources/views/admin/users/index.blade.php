@extends('layouts.admin')

@php
    $breadcrumbs = [
        ['title' => 'User Management']
    ];

    $adminTabQuery = array_merge(request()->except(['tab', 'page']), ['tab' => 'admins']);
    $brokerTabQuery = array_merge(request()->except(['tab', 'page', 'role']), ['tab' => 'brokers']);
    $currentResults = $tab === 'admins' ? $admins->count() : $brokers->count();
@endphp

@section('content')
<div class="w-full">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                            <p class="mt-2 text-sm text-gray-600">Manage admins, staff members, and brokers from one place.</p>
                        </div>
                        <a href="{{ route('admin.users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm inline-flex items-center gap-2">
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
                        </nav>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-lg mb-6">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="p-4 md:p-6 space-y-4">
                        <input type="hidden" name="tab" value="{{ $tab }}">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="{{ $tab === 'admins' ? 'md:col-span-2' : 'md:col-span-3' }}">
                                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                                <input type="text"
                                       name="search"
                                       id="search"
                                       value="{{ $search }}"
                                       placeholder="{{ $tab === 'admins' ? 'Search by name, email, position, or contact number' : 'Search by name, email, stall, address, or contact number' }}"
                                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="status"
                                        id="status"
                                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="deactivated" {{ $status === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                                </select>
                            </div>

                            @if($tab === 'admins')
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                    <select name="role"
                                            id="role"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                        <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All roles</option>
                                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <p class="text-sm text-gray-500">Showing {{ $currentResults }} result{{ $currentResults === 1 ? '' : 's' }}.</p>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.users.index', ['tab' => $tab]) }}"
                                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                    Reset
                                </a>
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    @if($tab === 'admins')
                        @include('admin.users.admin-list', ['admins' => $admins, 'count' => $count])
                    @else
                        @include('admin.users.broker-list', ['brokers' => $brokers, 'count' => $count])
                    @endif
                </div>
            </div>

<!-- Users page specific JS -->
<script src="{{ asset('js/user.js') }}" defer></script>
@endsection
