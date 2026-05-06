<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Flash messages for Toastr (read by app.js) -->
    <meta name="flash-success" content="{{ session('success') }}">
    <meta name="flash-error" content="{{ session('error') }}">
    <meta name="flash-warning" content="{{ session('warning') }}">
    <meta name="flash-info" content="{{ session('info') }}">

    <title>{{ config('app.name', 'POS System') }} - Broker</title>

    <!-- Compiled CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filter-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/next-gen-ui.css') }}">

    <!-- POS System Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
@php
    $impersonatedBroker = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::getImpersonatedBrokerForAdmin(auth()->user())
        : null;
    $brokerSupportActionsEnabled = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::areAdminBrokerSupportActionsEnabled(auth()->user())
        : false;
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
@endphp
<body class="shell-body theme-broker">
    <div class="shell-frame min-h-screen" x-data="{ reportsOpen: false, sidebarOpen: true }" @toggle-sidebar.window="sidebarOpen = !sidebarOpen">
        <div class="shell-orb shell-orb-one"></div>
        <div class="shell-orb shell-orb-two"></div>
        <!-- Sidebar Component -->
        @include('layouts.partials.broker-sidebar')

        <!-- Main Content -->
        <div class="app-main-shell main-content min-h-screen flex-1 flex flex-col overflow-hidden transition-all duration-300 ease-in-out md:ml-0" :class="sidebarOpen ? 'md:ml-64' : 'md:ml-16'">
            <!-- Navbar Component -->
            @include('layouts.partials.broker-navbar')

            @if($impersonatedBroker)
                <div class="px-6 pt-4">
                    <div class="flex flex-col gap-4 rounded-2xl border {{ $brokerViewReadOnly ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50' }} px-5 py-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $brokerViewReadOnly ? 'text-amber-700' : 'text-red-700' }}">
                                {{ $brokerViewReadOnly ? 'Admin Broker View · Read Only' : 'Admin Broker View · Support Actions Enabled' }}
                            </p>
                            <p class="mt-2 text-sm {{ $brokerViewReadOnly ? 'text-amber-900' : 'text-red-900' }}">
                                Signed in as <span class="font-semibold">{{ auth()->user()->name }}</span> and currently viewing
                                <span class="font-semibold">{{ $impersonatedBroker->name }}</span>
                                @if($impersonatedBroker->stall_name)
                                    <span class="{{ $brokerViewReadOnly ? 'text-amber-700' : 'text-red-700' }}">({{ $impersonatedBroker->stall_name }})</span>
                                @endif
                                in the broker workspace. {{ $brokerViewReadOnly ? 'Create, update, return, and delete actions stay locked until you explicitly enable support actions.' : 'All broker write actions are currently unlocked for support work and will affect this broker’s records.' }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            @if($brokerSupportActionsEnabled)
                                <form method="POST" action="{{ route('admin.broker-view.support.disable') }}" class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 transition-colors hover:bg-red-100">
                                        Return To Read-Only
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.broker-view.support.enable') }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-800 transition-colors hover:bg-amber-100">
                                        Enable Support Actions
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.broker-view.stop') }}" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl border {{ $brokerViewReadOnly ? 'border-amber-300 text-amber-800 hover:bg-amber-100' : 'border-red-300 text-red-800 hover:bg-red-100' }} bg-white px-4 py-2.5 text-sm font-semibold transition-colors">
                                    Return To Admin
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main class="app-main flex-1 overflow-auto p-6 pb-24 md:pb-6">
                @yield('content')
            </main>

            <!-- Desktop Footer -->
            @include('layouts.partials.desktop-footer-polished')
        </div>

        <!-- Mobile Footer Sidebar -->
        @include('layouts.partials.broker-mobile-footer-sidebar-polished')

        <!-- Profile Modal -->
        @include('auth.profile')
    </div>
</body>
</html>
