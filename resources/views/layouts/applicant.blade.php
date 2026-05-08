<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="flash-success" content="{{ session('success') }}">
    <meta name="flash-error" content="{{ session('error') }}">
    <meta name="flash-warning" content="{{ session('warning') }}">
    <meta name="flash-info" content="{{ session('info') }}">

    <title>{{ config('app.name', 'POS System') }} - Applicant</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filter-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/next-gen-ui.css') }}">

    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="shell-body theme-broker applicant-shell">
    <div class="shell-frame min-h-screen" x-data="{ sidebarOpen: true }" @toggle-sidebar.window="sidebarOpen = !sidebarOpen">
        <div class="shell-orb shell-orb-one"></div>
        <div class="shell-orb shell-orb-two"></div>

        @include('layouts.partials.applicant-sidebar')

        <div class="app-main-shell main-content min-h-screen flex-1 flex flex-col overflow-hidden md:ml-64" :class="sidebarOpen ? 'md:ml-64' : 'md:ml-16'">
            @include('layouts.partials.applicant-navbar')

            <main class="app-main flex-1 overflow-auto p-6 pb-24 md:pb-6">
                @yield('content')
            </main>

            @include('layouts.partials.desktop-footer-polished')
        </div>
    </div>

    @include('auth.profile')
</body>
</html>
