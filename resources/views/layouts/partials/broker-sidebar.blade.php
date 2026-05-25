<!-- Broker Sidebar Component -->
@php
    $isCashierStaff = auth()->check() && auth()->user()->isCashier();
    $isBrokerTrackingActive = request()->routeIs('broker.fish-boxes.tracking');
    $isTransactionActive = request()->routeIs('broker.transaction');
    $isSalesRecordsActive = request()->routeIs('broker.sales.sales');
@endphp

<div :class="sidebarOpen ? 'w-64' : 'w-16'" class="app-sidebar broker-sidebar fixed left-0 top-0 z-40 hidden min-h-screen overflow-hidden transition-all duration-200 ease-out md:block">
    <div class="app-sidebar-brand border-b p-4">
        <div class="flex items-center space-x-2">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-clear-600">
                <img src="{{ asset('image/logo-small.png') }}" alt="" class="h-12 w-12 object-contain">
            </div>
            <div x-show="sidebarOpen" class="min-w-0 whitespace-nowrap">
                <span class="app-sidebar-title block text-xl font-bold text-gray-800">{{ $isCashierStaff ? 'Cashier' : 'Broker' }}</span>
                <span class="app-sidebar-subtitle block">{{ $isCashierStaff ? 'POS Desk' : 'Market Desk' }}</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-8 space-y-2">
        <!-- MENU Section -->
        <div class="space-y-1">
            <div class="app-sidebar-section px-3 py-2 text-xs font-semibold uppercase tracking-wider transition-all duration-200">
                Menu
            </div>

            @unless($isCashierStaff)
                <!-- Dashboard -->
                <div>
                <a href="{{ route('broker.dashboard') }}"
                   class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                          {{ request()->routeIs('broker.dashboard') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-squares-2x2 class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                               {{ request()->routeIs('broker.dashboard') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                      <span class="transition-all duration-200" x-show="sidebarOpen">Dashboard</span>
                 </a>
             </div>
                <!-- Inventory & Pricing -->
                <div>
                    <a href="{{ route('broker.inventory.index') }}"
                        class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                            {{ request()->routeIs('broker.inventory.*') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <x-heroicon-o-cube class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                            {{ request()->routeIs('broker.inventory.*') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                            <span class="transition-all duration-200" x-show="sidebarOpen">Inventory & Pricing</span>
                        </a>
                    </div>
            @endunless
            <!-- Transaction -->
            <div>
                <a href="{{ route('broker.transaction') }}"
                class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                        {{ $isTransactionActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-plus-circle class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                            {{ $isTransactionActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="transition-all duration-200" x-show="sidebarOpen">{{ $isCashierStaff ? 'POS Terminal' : 'Transaction' }}</span>
                </a>
            </div>
            <!-- Sales -->
            <div>
                <a href="{{ route('broker.sales.sales') }}"
                class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                        {{ $isSalesRecordsActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-banknotes class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                            {{ $isSalesRecordsActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="transition-all duration-200" x-show="sidebarOpen">{{ $isCashierStaff ? 'My Transactions' : 'Sales Records' }}</span>
                </a>
            </div>
            @unless($isCashierStaff)
            <!-- Fish Box Tracking -->
            <div>
                <a href="{{ route('broker.fish-boxes.tracking') }}"
                class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                        {{ $isBrokerTrackingActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-archive-box class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                            {{ $isBrokerTrackingActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="transition-all duration-200" x-show="sidebarOpen">Fish Box Tracking</span>
                </a>
            </div>
            <!-- Analytics -->
            <div>
                <a href="{{ route('broker.sales.analytics') }}"
                class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                        {{ request()->routeIs('broker.sales.analytics') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-chart-bar class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                            {{ request()->routeIs('broker.sales.analytics') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                     <span class="transition-all duration-200" x-show="sidebarOpen">Analytics</span>
                 </a>
             </div>

            <!-- Financial Statement -->
            <div>
                <a href="{{ route('broker.financial-statements.index') }}"
                class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                        {{ request()->routeIs('broker.financial-statements.*') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-calculator class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                            {{ request()->routeIs('broker.financial-statements.*') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="transition-all duration-200" x-show="sidebarOpen">Income And Expenses</span>
                </a>
            </div>
            @endunless

      </div>
      </nav>
</div>
