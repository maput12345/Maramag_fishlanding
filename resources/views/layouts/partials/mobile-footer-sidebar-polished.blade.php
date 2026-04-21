<!-- Mobile Footer Sidebar Component -->
<div class="app-mobile-dock fixed bottom-0 left-0 right-0 z-50 border-t md:hidden">
    <div class="flex items-center justify-around py-2">
        <div class="group relative">
            <a href="{{ route('admin.dashboard') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('admin.dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.dashboard') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Dashboard</span>
            </a>
        </div>

        <div class="group relative">
            <a href="{{ route('admin.users.index') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('admin.users.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-users class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.users.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Users</span>
            </a>
        </div>

        <div class="group relative">
            <a href="{{ route('admin.sales.index') }}"
               class="flex flex-col items-center justify-center rounded-lg p-2 transition-all duration-200 ease-in-out {{ request()->routeIs('admin.sales.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-chart-bar class="h-6 w-6 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.sales.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="mt-1 text-xs font-medium">Reports</span>
            </a>
        </div>
    </div>

    <div class="border-t border-white/40 px-4 py-2">
        <div class="flex items-center justify-center text-xs text-gray-500">
            <span class="mr-1">Copyright &copy;</span>
            <span>2025 <span class="font-bold text-blue-600">JJI Devz</span>. All rights reserved.</span>
        </div>
    </div>
</div>
