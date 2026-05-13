<!-- Broker Mobile Footer Sidebar Component -->
@php
    $isBrokerTrackingActive = request()->routeIs('broker.fish-boxes.tracking');
    $isTransactionActive = request()->routeIs('broker.transaction');
    $isSalesRecordsActive = request()->routeIs('broker.sales.sales');
@endphp

<div class="app-mobile-dock fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white shadow-lg md:hidden">
    <div class="flex items-center justify-around py-2">
        <div class="group relative">
            <a href="{{ route('broker.dashboard') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('broker.dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('broker.dashboard') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Dashboard</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Dashboard
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <div class="group relative">
            <a href="{{ route('broker.inventory.index') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('broker.inventory.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-cube class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('broker.inventory.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Inventory</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Inventory & Pricing
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <div class="group relative">
            <a href="{{ route('broker.transaction') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ $isTransactionActive ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-plus-circle class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ $isTransactionActive ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Cashier</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Cashier Transaction
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <div class="group relative">
            <a href="{{ route('broker.sales.sales') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ $isSalesRecordsActive ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-banknotes class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ $isSalesRecordsActive ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Sales</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Sales Records
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <div class="group relative">
            <a href="{{ route('broker.fish-boxes.tracking') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ $isBrokerTrackingActive ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-archive-box class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ $isBrokerTrackingActive ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Tracking</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Fish Box Management
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <div class="group relative">
            <a href="{{ route('broker.sales.analytics') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('broker.sales.analytics') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-chart-bar class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('broker.sales.analytics') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Analytics</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Analytics
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <div class="group relative">
            <a href="{{ route('broker.financial-statements.index') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('broker.financial-statements.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-calculator class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('broker.financial-statements.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Finance</span>
            </a>
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                Income Statement
                <div class="absolute left-1/2 top-full -translate-x-1/2 transform border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-200 bg-gray-50 px-4 py-2">
        <div class="flex items-center justify-center text-xs text-gray-500">
            <span class="mr-1">Copyright &copy;</span>
            <span>2025 <span class="font-bold text-blue-600">JJI Devz</span>. All rights reserved.</span>
        </div>
    </div>
</div>
