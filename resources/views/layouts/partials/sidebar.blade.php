<!-- Admin Sidebar Component -->
@php
    $isAdminSalesMenuActive = request()->routeIs('admin.sales.index');
    $isAdminFishBoxTrackingActive = request()->routeIs('admin.sales.tracking');
    $isAdminApplicantMenuActive = request()->routeIs('admin.applications.*');
    $isAdminStallMenuActive = request()->routeIs('admin.stalls.*');
    $isAdminStallIndexActive = request()->routeIs('admin.stalls.index');
    $isAdminStallRequirementsActive = request()->routeIs('admin.stalls.requirements.*');
    $isAdminStallOverviewActive = request()->routeIs('admin.stalls.overview');
    $canAccessUserManagement = auth()->check() && auth()->user()->isAdmin();
    $canAccessAdminFishBoxTracking = auth()->check() && auth()->user()->isAdmin();
@endphp

<div
    class="app-sidebar admin-sidebar fixed left-0 top-0 z-40 hidden min-h-screen w-64 overflow-hidden transition-all duration-300 ease-in-out md:block"
    data-admin-sidebar
>
    <div class="app-sidebar-brand border-b p-4">
        <div class="flex items-center space-x-2">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-clear-600">
                <img src="{{ asset('image/logo-small.png') }}" alt="" class="h-12 w-12 object-contain">
            </div>
            <div class="min-w-0 whitespace-nowrap" data-admin-sidebar-expanded>
                <span class="app-sidebar-title block text-xl font-bold text-gray-800">LEEO</span>
                <span class="app-sidebar-subtitle block">Command Center</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-8 space-y-2">
        <!-- MENU Section -->
        <div class="space-y-1">
            <div class="app-sidebar-section px-3 py-2 text-xs font-semibold uppercase tracking-wider transition-all duration-200" data-admin-sidebar-expanded>
                Menu
            </div>

            <!-- Dashboard -->
            <div>
            <a href="{{ route('admin.dashboard') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ request()->routeIs('admin.dashboard') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-squares-2x2 class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ request()->routeIs('admin.dashboard') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" data-admin-sidebar-expanded>Dashboard</span>
             </a>
         </div>

         @if($canAccessUserManagement)
             <!-- User Management -->
             <div>
                <a href="{{ route('admin.users.index') }}"
                   class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                          {{ request()->routeIs('admin.users.*') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-users class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                               {{ request()->routeIs('admin.users.*') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                      <span class="transition-all duration-200" data-admin-sidebar-expanded>User Management</span>
                 </a>
             </div>
         @endif


         <!-- Sale -->
         <div>
            <a href="{{ route('admin.sales.index') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ $isAdminSalesMenuActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-banknotes class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ $isAdminSalesMenuActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" data-admin-sidebar-expanded>Sales</span>
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
                      <span class="transition-all duration-200" data-admin-sidebar-expanded>Fish Box Tracking</span>
                 </a>
             </div>
         @endif

         <div>
            <a href="{{ route('admin.applications.index') }}"
               class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                      {{ $isAdminApplicantMenuActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-heroicon-o-clipboard-document-check class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ $isAdminApplicantMenuActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                  <span class="transition-all duration-200" data-admin-sidebar-expanded>Applicant</span>
             </a>
         </div>

         <details class="space-y-2" data-admin-stall-menu {{ $isAdminStallMenuActive ? 'open' : '' }}>
            <summary
                class="app-shell-link group flex w-full cursor-pointer items-center rounded-md px-2 py-2 text-left text-sm font-medium transition-all duration-200 ease-in-out
                       {{ $isAdminStallMenuActive ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
            >
                <x-heroicon-o-building-storefront class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                           {{ $isAdminStallMenuActive ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                <span class="flex-1 transition-all duration-200" data-admin-sidebar-expanded>Stall</span>
                <x-heroicon-o-chevron-right
                    class="ml-auto h-4 w-4 flex-shrink-0 text-slate-400 transition-transform duration-200 group-hover:text-slate-600 {{ $isAdminStallMenuActive ? 'text-white' : '' }}"
                    data-admin-sidebar-expanded
                />
             </summary>
             <div class="ml-5 border-l border-slate-700/20 pl-3 transition-all duration-200 ease-out" data-admin-sidebar-expanded>
                <div class="space-y-1 py-1">
                    <a href="{{ route('admin.stalls.index') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-200 {{ $isAdminStallIndexActive ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                        Open Stall
                    </a>
                    <a href="{{ route('admin.stalls.requirements.index') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-200 {{ $isAdminStallRequirementsActive ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                        Requirements
                    </a>
                    <a href="{{ route('admin.stalls.overview') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-200 {{ $isAdminStallOverviewActive ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                        Stall Overview
                    </a>
                </div>
             </div>
         </details>
     </div>
     </nav>
</div>
