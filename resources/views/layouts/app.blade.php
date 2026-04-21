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

    <title>{{ config('app.name', 'POS System') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Compiled CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/next-gen-ui.css') }}">


    <!-- Alpine.js for interactive components -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- POS System Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="@yield('body-class', 'login-shell theme-admin')">
    @yield('content')
</body>
</html>
