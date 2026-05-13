@php
    $hasActiveApplication = auth()->check()
        && auth()->user()->brokerApplications()
            ->whereNotIn('application_status', ['Rejected', 'Not Selected', 'Cancelled'])
            ->exists();
    $isApplyMode = request()->routeIs('applications.create') || request('focus') === 'apply';
    $isApplicantHome = request()->routeIs('applications.index') && !$isApplyMode;
    $applyUrl = route('applications.index', ['focus' => 'apply']) . '#open-stalls';
@endphp

<div :class="sidebarOpen ? 'w-64' : 'w-16'" class="app-sidebar broker-sidebar fixed left-0 top-0 z-40 hidden min-h-screen overflow-hidden transition-all duration-200 ease-out md:block">
    <div class="app-sidebar-brand border-b p-4">
        <div class="flex items-center space-x-2">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-clear-600">
                <img src="{{ asset('image/logo-small.png') }}" alt="Maramag Fish Landing logo" class="h-12 w-12 object-contain">
            </div>
            <div x-show="sidebarOpen" class="min-w-0 whitespace-nowrap">
                <span class="app-sidebar-title block text-xl font-bold text-gray-800">Applicant</span>
                <span class="app-sidebar-subtitle block">Application Desk</span>
            </div>
        </div>
    </div>

    <nav class="mt-8 space-y-2">
        <div class="space-y-1">
            <div class="app-sidebar-section px-3 py-2 text-xs font-semibold uppercase tracking-wider transition-all duration-200">
                Menu
            </div>

            <div>
                <a href="{{ route('applications.index') }}"
                   class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                          {{ $isApplicantHome ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-squares-2x2 class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                               {{ $isApplicantHome ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="transition-all duration-200" x-show="sidebarOpen">Applicant Home</span>
                </a>
            </div>

            @unless($hasActiveApplication)
                <div>
                    <a href="{{ $applyUrl }}"
                       class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                              {{ $isApplyMode ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        <x-heroicon-o-building-storefront class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                                   {{ $isApplyMode ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                        <span class="transition-all duration-200" x-show="sidebarOpen">Apply for Stall</span>
                    </a>
                </div>
            @endunless

            <div>
                <a href="{{ route('applications.my-applications') }}"
                   class="app-shell-link group flex items-center rounded-md px-2 py-2 text-sm font-medium transition-all duration-200 ease-in-out
                          {{ request()->routeIs('applications.my-applications') || request()->routeIs('applications.show') || request()->routeIs('applications.edit') ? 'app-shell-link--active' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-heroicon-o-document-text class="h-6 w-6 flex-shrink-0 transition-transform duration-200 group-hover:scale-110 sidebar-icon
                               {{ request()->routeIs('applications.my-applications') || request()->routeIs('applications.show') || request()->routeIs('applications.edit') ? '' : 'text-gray-400 group-hover:text-gray-500' }}" />
                    <span class="transition-all duration-200" x-show="sidebarOpen">My Applications</span>
                </a>
            </div>
        </div>
    </nav>
</div>
