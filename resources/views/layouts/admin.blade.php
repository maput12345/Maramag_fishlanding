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

    <title>{{ config('app.name', 'POS System') }} - Admin</title>
    @include('layouts.partials.favicons')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Compiled CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filter-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/next-gen-ui.css') }}">

    <!-- POS System Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="shell-body theme-admin">
    <div class="shell-frame min-h-screen">
        <div class="shell-orb shell-orb-one"></div>
        <div class="shell-orb shell-orb-two"></div>
        <!-- Sidebar Component -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <div
            class="app-main-shell admin-main-shell main-content min-h-screen min-w-0 flex-1 flex flex-col overflow-hidden md:ml-64"
            data-admin-main
        >
            <!-- Navbar Component -->
            @include('layouts.partials.navbar')

            <!-- Page Content -->
            <main class="app-main flex-1 min-w-0 overflow-y-auto overflow-x-hidden p-6 pb-24 md:pb-6">
                @yield('content')
            </main>

            <!-- Desktop Footer -->
            @include('layouts.partials.desktop-footer-polished')
        </div>

        <!-- Mobile Footer Sidebar -->
        @include('layouts.partials.mobile-footer-sidebar-polished')

        <!-- Profile Modal -->
        @include('auth.profile')
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.querySelector('[data-admin-sidebar-toggle]');
            const sidebar = document.querySelector('[data-admin-sidebar]');
            const main = document.querySelector('[data-admin-main]');
            const expandedItems = document.querySelectorAll('[data-admin-sidebar-expanded]');
            const stallMenus = document.querySelectorAll('[data-admin-stall-menu]');

            if (!toggle || !sidebar || !main) {
                return;
            }

            const setCollapsed = function (collapsed) {
                sidebar.classList.toggle('w-64', !collapsed);
                sidebar.classList.toggle('w-16', collapsed);
                main.classList.toggle('md:ml-64', !collapsed);
                main.classList.toggle('md:ml-16', collapsed);

                expandedItems.forEach(function (item) {
                    item.hidden = collapsed;
                });

                if (collapsed) {
                    stallMenus.forEach(function (menu) {
                        menu.open = false;
                    });
                }
            };

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();

                setCollapsed(sidebar.classList.contains('w-64'));
            }, true);
        });
    </script>
</body>
</html>
