@php
    $resolvedTopbarAction = $topbarAction ?? [
        'label' => null,
        'url' => null,
        'modal' => false,
    ];

    $impersonatedBroker = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::getImpersonatedBrokerForAdmin(auth()->user())
        : null;
    $brokerSupportActionsEnabled = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::areAdminBrokerSupportActionsEnabled(auth()->user())
        : false;
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;

    if ($brokerViewReadOnly) {
        $resolvedTopbarAction = [
            'label' => null,
            'url' => null,
            'modal' => false,
        ];
    }
@endphp

<!-- Broker Navbar Component -->
<header class="app-topbar">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center space-x-4">
            <button @click="$dispatch('toggle-sidebar')" class="hidden md:block text-gray-500 hover:text-gray-700 transition-colors">
                <x-heroicon-o-bars-3 class="w-6 h-6" />
            </button>

            <!-- Mobile Brand -->
            <div class="md:hidden">
                <a href="{{ route('broker.dashboard') }}" class="flex items-center">
                    <img src="{{ asset('image/logo-small.png') }}"
                         alt="Maramag Fish Landing logo"
                         class="h-10 w-10 object-contain">
                </a>
            </div>

            <!-- Breadcrumbs -->
            <nav class="hidden lg:block text-sm">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('broker.dashboard') }}" class="text-gray-500 hover:text-gray-700">Broker</a>
                    </li>
                    @if(isset($breadcrumbs) && !empty($breadcrumbs))
                        @foreach($breadcrumbs as $breadcrumb)
                            <li>
                                <span class="text-gray-300">/</span>
                            </li>
                            <li>
                                @if(isset($breadcrumb['url']))
                                    <a href="{{ $breadcrumb['url'] }}" class="text-gray-500 hover:text-gray-700">{{ $breadcrumb['title'] }}</a>
                                @else
                                    <span class="{{ $loop->last ? 'text-gray-900' : 'text-gray-700' }}">{{ $breadcrumb['title'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    @endif
                </ol>
            </nav>
        </div>

        <div class="flex items-center space-x-4">
            @if(!empty($resolvedTopbarAction['url']) && !empty($resolvedTopbarAction['label']))
                <a
                    href="{{ $resolvedTopbarAction['url'] }}"
                    class="hidden md:flex bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm"
                    @if(!empty($resolvedTopbarAction['modal'])) data-sales-modal-link @endif
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    {{ $resolvedTopbarAction['label'] }}
                </a>

                <a
                    href="{{ $resolvedTopbarAction['url'] }}"
                    class="inline-flex md:hidden items-center justify-center rounded-xl bg-green-600 p-3 text-white shadow-sm transition-colors hover:bg-green-700"
                    aria-label="{{ $resolvedTopbarAction['label'] }}"
                    title="{{ $resolvedTopbarAction['label'] }}"
                    @if(!empty($resolvedTopbarAction['modal'])) data-sales-modal-link @endif
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </a>
            @endif
            <!-- User Dropdown -->
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

                <!-- Dropdown Menu -->
                <div hidden
                     data-profile-menu-panel
                     class="app-dropdown-panel absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg z-50">
                    <div class="py-1">
                        <div class="px-4 py-2 text-sm text-gray-700 border-b">
                            <div class="font-medium">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                            <div class="text-xs font-medium {{ $impersonatedBroker ? ($brokerViewReadOnly ? 'text-amber-600' : 'text-red-600') : 'text-blue-600' }}">
                                @if($impersonatedBroker)
                                    {{ $brokerViewReadOnly ? 'Admin read-only broker view' : 'Admin support actions enabled' }}
                                @else
                                    Broker
                                @endif
                            </div>
                        </div>
                        <a href="{{ url()->current() }}?modal=profile" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <x-heroicon-o-user class="w-4 h-4 mr-3" />
                            Profile
                        </a>
                        @if($impersonatedBroker)
                            @if($brokerSupportActionsEnabled)
                                <form method="POST" action="{{ route('admin.broker-view.support.disable') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                        <x-heroicon-o-lock-closed class="w-4 h-4 mr-3" />
                                        Return to Read-Only
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.broker-view.support.enable') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-amber-700 hover:bg-amber-50">
                                        <x-heroicon-o-lock-open class="w-4 h-4 mr-3" />
                                        Enable Support Actions
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.broker-view.stop') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-amber-700 hover:bg-amber-50">
                                    <x-heroicon-o-arrow-uturn-left class="w-4 h-4 mr-3" />
                                    Return to Admin
                                </button>
                            </form>
                        @endif
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
