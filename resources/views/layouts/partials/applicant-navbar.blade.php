@php
    $currentPage = match (true) {
        request()->routeIs('applications.create'), request('focus') === 'apply' => 'Apply for Stall',
        request()->routeIs('applications.my-applications'), request()->routeIs('applications.show'), request()->routeIs('applications.edit') => 'My Applications',
        default => 'Applicant Home',
    };
@endphp

<header class="app-topbar">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center space-x-4">
            <button @click="$dispatch('toggle-sidebar')" class="hidden md:block text-gray-500 hover:text-gray-700 transition-colors">
                <x-heroicon-o-bars-3 class="w-6 h-6" />
            </button>

            <div class="md:hidden">
                <a href="{{ route('applications.index') }}" class="flex items-center">
                    <img src="{{ asset('image/logo-small.png') }}"
                         alt="Maramag Fish Landing logo"
                         class="h-10 w-10 object-contain">
                </a>
            </div>

            <nav class="hidden lg:block text-sm">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('applications.index') }}" class="text-gray-500 hover:text-gray-700">Applicant</a>
                    </li>
                    <li>
                        <span class="text-gray-300">/</span>
                    </li>
                    <li>
                        <span class="text-gray-900">{{ $currentPage }}</span>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex items-center space-x-4">
            <div class="relative" data-profile-menu>
                <button
                    type="button"
                    data-profile-menu-button
                    aria-haspopup="true"
                    aria-expanded="false"
                    class="app-user-trigger flex items-center space-x-2 text-sm text-gray-700 transition-colors hover:text-gray-900"
                >
                    <div class="app-avatar-badge w-8 h-8 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <span class="hidden lg:inline">{{ auth()->user()->name }}</span>
                    <x-heroicon-o-chevron-down class="w-4 h-4 transition-transform duration-200" data-profile-menu-icon />
                </button>

                <div hidden
                     data-profile-menu-panel
                     class="app-dropdown-panel absolute right-0 mt-2 w-56 rounded-lg border border-gray-200 bg-white shadow-lg z-50">
                    <div class="py-1">
                        <div class="px-4 py-2 text-sm text-gray-700 border-b">
                            <div class="font-medium">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                            <div class="text-xs font-medium text-blue-600">Applicant</div>
                        </div>
                        <a href="{{ url()->current() }}?modal=profile" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <x-heroicon-o-user class="w-4 h-4 mr-3" />
                            Profile
                        </a>
                        <hr class="my-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 mr-3" />
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
