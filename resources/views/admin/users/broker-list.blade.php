<!-- Broker Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-users class="w-4 h-4 md:w-5 md:h-5 text-blue-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Total Brokers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['totalBrokers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-check-circle class="w-4 h-4 md:w-5 md:h-5 text-green-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Active Brokers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['activeBrokers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-red-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-x-circle class="w-4 h-4 md:w-5 md:h-5 text-red-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Deactivated Brokers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['deactivatedBrokers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-trash class="w-4 h-4 md:w-5 md:h-5 text-gray-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Deleted Brokers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['deletedBrokers'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Broker List Tab Content -->
@php
    $activeBrokerViewId = auth()->user()?->isAdmin()
        ? \App\Models\Broker::getImpersonatedBrokerForAdmin(auth()->user())?->id
        : null;
@endphp

<div class="space-y-4">
    <!-- Broker Users Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Broker Directory</h3>
            <span class="text-sm text-gray-500">{{ $brokers->count() }} result{{ $brokers->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Broker User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($brokers as $broker)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-medium text-sm bg-blue-500">
                                    <span>{{ substr($broker->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $broker->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $broker->stall_name ?: $broker->address }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $broker->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-status-badge :status="$broker->status" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $broker->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                @if(auth()->user()?->isAdmin())
                                    @if($activeBrokerViewId === $broker->id)
                                        <x-status-badge status="Ongoing" label="Viewing Now" />
                                    @elseif($broker->user?->status === \App\Constants\UserStatusConstant::ACTIVE)
                                        <form method="POST" action="{{ route('admin.broker-view.start', $broker) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="app-button app-button--primary px-3 py-2 text-xs">
                                                Broker View
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                <span class="relative group inline-block">
                                    <a href="{{ route('admin.users.edit', $broker->user->id) }}" class="app-icon-button app-icon-button--edit" aria-label="Edit">
                                        <x-heroicon-o-pencil-square class="w-6 h-6" />
                                    </a>
                                    <span class="app-tooltip">Edit</span>
                                </span>

                                @if($broker->status === 'active')
                                    <form method="POST" action="{{ route('admin.users.deactivate', $broker->user->id) }}" class="inline" data-swal="deactivate">
                                        @csrf
                                        @method('PATCH')
                                        <span class="relative group inline-block">
                                            <button type="submit" class="app-icon-button app-icon-button--deactivate" aria-label="Deactivate">
                                                <x-heroicon-o-user-minus class="w-6 h-6" />
                                            </button>
                                            <span class="app-tooltip">Deactivate</span>
                                        </span>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.activate', $broker->user->id) }}" class="inline" data-swal="activate">
                                        @csrf
                                        @method('PATCH')
                                        <span class="relative group inline-block">
                                            <button type="submit" class="app-icon-button app-icon-button--activate" aria-label="Activate">
                                                <x-heroicon-o-user-plus class="w-6 h-6" />
                                            </button>
                                            <span class="app-tooltip">Activate</span>
                                        </span>
                                    </form>
                                @endif

                                @if($broker->stall_id && $broker->status === 'active')
                                    <form method="POST"
                                          action="{{ route('admin.brokers.release-stall', $broker) }}"
                                          class="inline"
                                          data-swal="release"
                                          data-swal-title="Release this stall?"
                                          data-swal-text="This will make {{ $broker->stall_name ?: 'the stall' }} vacant and return {{ $broker->user->email }} to applicant access. The account can apply again."
                                          data-swal-confirm="Yes, release stall"
                                          data-swal-icon="warning">
                                        @csrf
                                        @method('PATCH')
                                        <span class="relative group inline-block">
                                            <button type="submit" class="app-icon-button app-icon-button--release" aria-label="Release Stall">
                                                <x-heroicon-o-arrow-path class="w-6 h-6" />
                                            </button>
                                            <span class="app-tooltip">Release Stall</span>
                                        </span>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.users.destroy', $broker->user->id) }}" class="inline" data-swal="delete">
                                    @csrf
                                    @method('DELETE')
                                    <span class="relative group inline-block">
                                        <button type="submit" class="app-icon-button app-icon-button--delete" aria-label="Delete">
                                            <x-heroicon-o-trash class="w-6 h-6" />
                                        </button>
                                        <span class="app-tooltip">Delete</span>
                                    </span>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No brokers matched the current filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
