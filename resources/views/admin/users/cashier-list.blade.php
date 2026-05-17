<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="p-2 md:p-3 rounded-full bg-blue-100">
                <x-heroicon-o-calculator class="w-5 h-5 md:w-6 md:h-6 text-blue-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Total Cashiers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['totalCashiers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="p-2 md:p-3 rounded-full bg-green-100">
                <x-heroicon-o-check-circle class="w-5 h-5 md:w-6 md:h-6 text-green-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Active Cashiers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['activeCashiers'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="p-2 md:p-3 rounded-full bg-red-100">
                <x-heroicon-o-x-circle class="w-5 h-5 md:w-6 md:h-6 text-red-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Inactive Cashiers</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['deactivatedCashiers'] }}</p>
            </div>
        </div>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Broker Cashier Staff</h3>
            <span class="text-sm text-gray-500">{{ $cashiers->count() }} result{{ $cashiers->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Broker</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cashiers as $cashier)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                        <span>{{ substr($cashier->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $cashier->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $cashier->employee?->contact_number ?: 'No contact number' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $cashier->brokerStaff?->broker?->name ?? 'Not assigned' }}</div>
                                <div class="text-sm text-gray-500">{{ $cashier->brokerStaff?->broker?->stall_name ?? $cashier->brokerStaff?->broker?->business_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $cashier->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-status-badge :status="$cashier->status" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.users.edit', $cashier->id) }}" class="app-icon-button app-icon-button--edit" aria-label="Edit">
                                        <x-heroicon-o-pencil-square class="w-6 h-6" />
                                    </a>
                                    @if($cashier->status === 'active')
                                        <form method="POST" action="{{ route('admin.users.deactivate', $cashier->id) }}" class="inline" data-swal="deactivate">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="app-icon-button app-icon-button--deactivate" aria-label="Deactivate">
                                                <x-heroicon-o-user-minus class="w-6 h-6" />
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.activate', $cashier->id) }}" class="inline" data-swal="activate">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="app-icon-button app-icon-button--activate" aria-label="Activate">
                                                <x-heroicon-o-user-plus class="w-6 h-6" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No cashier staff matched the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
