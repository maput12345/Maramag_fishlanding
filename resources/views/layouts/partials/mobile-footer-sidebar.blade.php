<!-- Mobile Footer Sidebar Component -->
@php
    $isAdminSalesMenuActive = request()->routeIs('admin.sales.index');
    $isAdminFishBoxTrackingActive = request()->routeIs('admin.sales.tracking');
    $canAccessAdminFishBoxTracking = auth()->check() && auth()->user()->isAdmin();
@endphp

<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
    <div class="flex justify-around items-center py-2">
        <!-- Dashboard -->
        <div class="relative group">
            <a href="{{ route('admin.dashboard') }}"
               class="flex flex-col items-center justify-center p-2 rounded-lg transition-all duration-200 ease-in-out
                      {{ request()->routeIs('admin.dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6 transition-transform duration-200 group-hover:scale-110
                           {{ request()->routeIs('admin.dashboard') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="text-xs mt-1 font-medium">Dashboard</span>
            </a>
            <!-- Tooltip -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
                Dashboard
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        <!-- User Management -->
        <div class="relative group">
            <a href="{{ route('admin.users.index') }}"
               class="flex flex-col items-center justify-center p-2 rounded-lg transition-all duration-200 ease-in-out
                      {{ request()->routeIs('admin.users.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-users class="h-6 w-6 transition-transform duration-200 group-hover:scale-110
                           {{ request()->routeIs('admin.users.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="text-xs mt-1 font-medium">Users</span>
            </a>
            <!-- Tooltip -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
                User Management
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>


        <!-- Sale -->
        <div class="relative group">
            <a href="{{ route('admin.sales.index') }}"
               class="flex flex-col items-center justify-center p-2 rounded-lg transition-all duration-200 ease-in-out
                      {{ $isAdminSalesMenuActive ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-banknotes class="h-6 w-6 transition-transform duration-200 group-hover:scale-110
                           {{ $isAdminSalesMenuActive ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="text-xs mt-1 font-medium">Sales</span>
            </a>
            <!-- Tooltip -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
                Sale
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>

        @if($canAccessAdminFishBoxTracking)
            <!-- Fish Box Tracking -->
            <div class="relative group">
                <a href="{{ route('admin.sales.tracking') }}"
                   class="flex flex-col items-center justify-center p-2 rounded-lg transition-all duration-200 ease-in-out
                          {{ $isAdminFishBoxTrackingActive ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-archive-box class="h-6 w-6 transition-transform duration-200 group-hover:scale-110
                               {{ $isAdminFishBoxTrackingActive ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="text-xs mt-1 font-medium">Tracking</span>
                </a>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap">
                    Fish Box Tracking
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Copyright Footer -->
    <div class="border-t border-gray-200 bg-gray-50 px-4 py-2">
        <div class="flex items-center justify-center text-xs text-gray-500">
            <span class="mr-1">Copyright ©</span>
            <span>2025 <span class="font-bold text-blue-600">JJI Devz</span>, All rights reserved.</span>
        </div>
    </div>
</div>
