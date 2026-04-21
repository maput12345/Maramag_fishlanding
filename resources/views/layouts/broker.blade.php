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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Compiled CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filter-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/next-gen-ui.css') }}">


    <!-- Alpine.js for interactive components -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- POS System Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
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
