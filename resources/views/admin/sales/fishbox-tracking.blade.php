<!-- Movement Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-8 h-8 md:w-12 md:h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                <x-heroicon-o-arrow-path class="w-4 h-4 md:w-6 md:h-6 text-white" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Currently Returned</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($summary['returned']) }}</p>
                <p class="text-xs text-yellow-600">Live box status</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-8 h-8 md:w-12 md:h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4 md:w-6 md:h-6 text-white" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Currently Missing</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ number_format($summary['missing']) }}</p>
                <p class="text-xs text-red-600">Live box status</p>
            </div>
        </div>
    </div>
</div>

<div class="space-y-10">
    <section class="space-y-5">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.sales.tracking') }}" x-data="{
                action: '{{ request('action') }}',
                dateFrom: '{{ request('date_from', '') }}',
                dateTo: '{{ request('date_to', '') }}'
            }" class="space-y-5">
                <div class="app-section-heading">
                    <h2 class="app-section-title">Movement Filters</h2>
                </div>
                <div class="filter-layout">
                    <div class="search-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="action" x-model="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="status-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date"
                               name="date_from"
                               x-model="dateFrom"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="fish-type-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date"
                               name="date_to"
                               x-model="dateTo"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="buttons-field flex justify-end space-x-2">
                        <a href="{{ route('admin.sales.tracking') }}"
                           class="app-button app-button--secondary">
                            Clear
                        </a>
                        <button type="submit"
                                class="app-button app-button--primary">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="px-1">
            <div class="app-section-heading">
                <h2 class="app-section-title">Current Fishbox Tracking</h2>
            </div>
        </div>
        <div class="px-1">
            <p class="text-sm text-gray-600">
                Showing {{ $trackedFishBoxes->firstItem() ?? 0 }} to {{ $trackedFishBoxes->lastItem() ?? 0 }} of {{ $trackedFishBoxes->total() }} tracked fish boxes
                @if(request()->hasAny(['action', 'date_from', 'date_to']))
                    <span class="text-blue-600">(filtered)</span>
                @endif
            </p>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish Box</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish Name</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trackedFishBoxes as $fishBox)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                    <span class="hidden md:inline">{{ $fishBox->updated_at->format('M d, Y H:i') }}</span>
                                    <span class="md:hidden">{{ $fishBox->updated_at->format('M d, H:i') }}</span>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    <div class="text-xs md:text-sm font-medium text-gray-900">{{ $fishBox->name }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ $fishBox->id }}</div>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    <div class="text-xs md:text-sm text-gray-900">{{ $fishBox->fish_type_name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'Returned' => 'bg-yellow-100 text-yellow-800',
                                            'Missing' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusClass = $statusColors[$fishBox->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                        {{ $fishBox->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500 font-mono">
                                    {{ Str::limit($fishBox->qr_code ?? 'N/A', 12) }}
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                                    <div class="text-gray-900">{{ $fishBox->broker?->name ?? 'System' }}</div>
                                    <div class="text-xs text-gray-500">{{ $fishBox->broker?->stall_name ?? 'No stall assigned' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 md:px-6 md:py-12 text-center text-gray-500">
                                    <x-heroicon-o-archive-box class="w-8 h-8 md:w-12 md:h-12 text-gray-400 mx-auto mb-3 md:mb-4" />
                                    <p class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">No current tracking rows found</p>
                                    <p class="text-xs md:text-sm">No fish boxes currently match the selected returned or missing status filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($trackedFishBoxes->hasPages())
                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $trackedFishBoxes->appends(request()->query())->links('components.pagination') }}
                </div>
            @endif
        </div>
    </section>

    <section class="space-y-5">
        <div class="px-1">
            <div class="app-section-heading">
                <h2 class="app-section-title">Inventory Log History</h2>
            </div>
        </div>

        <div class="px-1">
            <p class="text-sm text-gray-600">
                Showing {{ $inventoryLogs->firstItem() ?? 0 }} to {{ $inventoryLogs->lastItem() ?? 0 }} of {{ $inventoryLogs->total() }} logged events
                @if(request()->hasAny(['action', 'date_from', 'date_to']))
                    <span class="text-blue-600">(filtered)</span>
                @endif
            </p>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logged At</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish Box</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish Name</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                            <th class="px-3 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($inventoryLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                    <span class="hidden md:inline">{{ $log->created_at->format('M d, Y H:i') }}</span>
                                    <span class="md:hidden">{{ $log->created_at->format('M d, H:i') }}</span>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    <div class="text-xs md:text-sm font-medium text-gray-900">{{ $log->fishBox?->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ $log->fishBox?->id ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    <div class="text-xs md:text-sm text-gray-900">{{ $log->fishBox?->fish_type_name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    @php
                                        $actionColors = [
                                            'Returned' => 'bg-yellow-100 text-yellow-800',
                                            'Missing' => 'bg-red-100 text-red-800',
                                        ];
                                        $colorClass = $actionColors[$log->action] ?? 'bg-gray-100 text-gray-800';
                                        $currentStatus = $log->fishBox?->status;
                                        $currentStatusColors = [
                                            'In Stock' => 'bg-emerald-100 text-emerald-800',
                                            'Sold' => 'bg-blue-100 text-blue-800',
                                            'Returned' => 'bg-yellow-100 text-yellow-800',
                                            'Missing' => 'bg-red-100 text-red-800',
                                        ];
                                        $currentStatusClass = $currentStatusColors[$currentStatus] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $currentStatusClass }}">
                                        {{ $currentStatus ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500 font-mono">
                                    {{ Str::limit($log->fishBox?->qr_code ?? 'N/A', 12) }}
                                </td>
                                <td class="px-3 py-3 md:px-6 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                                    <div class="text-gray-900">{{ $log->broker?->name ?? 'System' }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->broker?->stall_name ?? 'No stall assigned' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 md:px-6 md:py-12 text-center text-gray-500">
                                    <x-heroicon-o-document-text class="w-8 h-8 md:w-12 md:h-12 text-gray-400 mx-auto mb-3 md:mb-4" />
                                    <p class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">No log history found</p>
                                    <p class="text-xs md:text-sm">No inventory movement events match your current filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($inventoryLogs->hasPages())
                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $inventoryLogs->appends(request()->query())->links('components.pagination') }}
                </div>
            @endif
        </div>
    </section>
</div>
