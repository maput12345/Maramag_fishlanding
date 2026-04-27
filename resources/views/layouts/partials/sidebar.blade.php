<!-- Admin Sidebar Component -->
@php
    $isAdminSalesMenuActive = request()->routeIs('admin.sales.index');
    $isAdminFishBoxTrackingActive = request()->routeIs('admin.sales.tracking');
    $isAdminApplicantMenuActive = request()->routeIs('admin.applications.*');
    $isAdminStallMenuActive = request()->routeIs('admin.stalls.*');
    $canAccessAdminFishBoxTracking = auth()->check() && auth()->user()->isAdmin();
@endphp

<div :class="sidebarOpen ? 'w-64' : 'w-16'" class="app-sidebar admin-sidebar fixed left-0 top-0 z-40 hidden min-h-screen overflow-hidden transition-all duration-300 ease-in-out md:block">
    <div class="app-sidebar-brand border-b p-4">
        <div class="flex items-center space-x-2">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-clear-600">
                <img src="{{ asset('image/logo.png') }}" alt="" class="h-12 w-12 object-contain">
            </div>
            <div x-show="sidebarOpen" x-transition class="min-w-0 whitespace-nowrap">
                <span class="app-sidebar-title block text-xl font-bold text-gray-800">LEEO</span>
                <span class="app-sidebar-subtitle block">Command Center</span>
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

            <!-- Dashboard -->
            <div>
            <a href="{{ route('admin.dashboard') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ request()->routeIs('admin.dashboard') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ request()->routeIs('admin.dashboard') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" x-show="sidebarOpen" x-transition>Dashboard</span>
             </a>
         </div>

         <!-- User Management -->
         <div>
            <a href="{{ route('admin.users.index') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ request()->routeIs('admin.users.*') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-users class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ request()->routeIs('admin.users.*') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" x-show="sidebarOpen" x-transition>User Management</span>
             </a>
         </div>


         <!-- Sale -->
         <div>
            <a href="{{ route('admin.sales.index') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ $isAdminSalesMenuActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-banknotes class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ $isAdminSalesMenuActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" x-show="sidebarOpen" x-transition>Sale</span>
             </a>
         </div>

         @if($canAccessAdminFishBoxTracking)
             <!-- Fish Box Tracking -->
             <div>
                <a href="{{ route('admin.sales.tracking') }}"
                   class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                          {{ $isAdminFishBoxTrackingActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-archive-box class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                               {{ $isAdminFishBoxTrackingActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                      <span class="transition-all duration-200" x-show="sidebarOpen" x-transition>Fish Box Tracking</span>
                 </a>
             </div>
         @endif

         <div>
            <a href="{{ route('admin.applications.index') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ $isAdminApplicantMenuActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-clipboard-document-check class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ $isAdminApplicantMenuActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" x-show="sidebarOpen" x-transition>Applicant</span>
             </a>
         </div>

         <div>
            <a href="{{ route('admin.stalls.index') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ $isAdminStallMenuActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-building-storefront class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ $isAdminStallMenuActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" x-show="sidebarOpen" x-transition>Stall</span>
             </a>
         </div>
     </div>
     </nav>
</div>
