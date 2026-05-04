<!-- Admin Sales Analysis Tab Content - Broker-centric view -->
<div class="space-y-6">
    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.sales.index') }}" x-data="{
            dateFrom: '{{ request('date_from', $dateFrom) }}',
            dateTo: '{{ request('date_to', $dateTo) }}',
            brokerSearch: '{{ request('broker_search', '') }}'
        }">
            <input type="hidden" name="tab" value="analysis">
            <div class="app-section-heading">
                <h2 class="app-section-title">Broker Sales</h2>
            </div>
            <div class="filter-layout">
                <!-- Broker Search -->
                <div class="search-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Broker</label>
                    <div class="relative">
                        <input type="text"
                               name="broker_search"
                               x-model="brokerSearch"
                               placeholder="Search broker name or stall..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Date From -->
                <div class="status-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date"
                           name="date_from"
                           x-model="dateFrom"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Date To -->
                <div class="fish-type-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date"
                           name="date_to"
                           x-model="dateTo"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Action Buttons -->
                <div class="buttons-field flex justify-end space-x-2">
                    <a href="{{ route('admin.sales.index') }}" class="app-button app-button--secondary">
                        Clear
                    </a>
                    <button type="submit" class="app-button app-button--primary">
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Active Brokers Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-users class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Brokers</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $brokersWithSales->total() }}</p>
                </div>
            </div>
        </div>

        <!-- Total Sales Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-shopping-cart class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Sales</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $brokersWithSales->sum(fn($broker) => $broker->sales->count()) }}</p>
                </div>
            </div>
        </div>

        <!-- Fishboxes Sold Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-archive-box class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Fishboxes Sold</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalFishBoxesSold) }}</p>
                </div>
            </div>
        </div>

        <!-- Date Range Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-calendar-days class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Date Range</p>
                    <p class="text-base font-bold text-gray-900">{{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d') }}</p>
                    <p class="text-xs text-purple-600">{{ \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1 }} days</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Brokers List with Sales -->
    @if($brokersWithSales->isEmpty())
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <x-heroicon-o-inbox class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Sales Found</h3>
            <p class="text-gray-600">No brokers have sales within the selected date range.</p>
        </div>
    @else
        <div class="space-y-4 sm:space-y-6">
            @foreach($brokersWithSales as $broker)
                @php
                    $brokerFishBoxCount = $broker->sales->sum(fn($sale) => $sale->salesDetails->sum('quantity'));
                    $brokerBuyerCount = $broker->sales
                        ->map(fn($sale) => $sale->buyer_id ?: trim((string) $sale->buyer_name))
                        ->filter(fn($buyerKey) => $buyerKey !== null && $buyerKey !== '')
                        ->unique()
                        ->count();
                    $receiptSalesCount = $broker->sales->count();
                    $receiptFishBoxCount = $brokerFishBoxCount;
                    $receiptSalesForPrint = $broker->sales
                        ->flatMap(function ($sale) {
                            return $sale->salesDetails->map(function ($detail) use ($sale) {
                                return [
                                    'date' => \Carbon\Carbon::parse($sale->sales_date)->format('M d, Y'),
                                    'buyer' => $sale->buyer_name,
                                    'fish_name' => $detail->item,
                                    'quantity' => $detail->quantity,
                                    'fish_boxes' => $detail->fishBoxes()->map(fn($fishBox) => $fishBox->name)->values()->all(),
                                ];
                            });
                        })
                        ->values();
                    $missingBoxesForPrint = $broker->missingFishBoxesForReceipt
                        ->map(function ($fishBox) {
                            $latestReceiptLog = $fishBox->inventoryLogs->first();

                            return [
                                'id' => $fishBox->id,
                                'name' => $fishBox->name,
                                'qr_code' => $fishBox->qr_code,
                                'reported_at' => $latestReceiptLog?->created_at?->format('M d, Y h:i A'),
                            ];
                        })
                        ->values();
                @endphp
                <div
                    class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden"
                    x-data="{ expanded: false }"
                    data-broker-id="{{ $broker->id }}"
                    data-broker-sales-count="{{ $broker->sales->count() }}"
                    data-broker-fishbox-count="{{ $brokerFishBoxCount }}"
                    data-receipt-date-from="{{ $dateFrom }}"
                    data-receipt-date-to="{{ $dateTo }}"
                    data-receipt-date="{{ $receiptDate }}"
                    data-receipt-sales-count="{{ $receiptSalesCount }}"
                    data-receipt-fishbox-count="{{ $receiptFishBoxCount }}"
                    data-receipt-leeo-commission-per-box="15"
                    data-receipt-data-url="{{ route('admin.sales.receipt-data', ['broker' => $broker->id], false) }}"
                    data-receipt-watermark-logo-url="{{ asset('image/logo.png') }}"
                    data-receipt-sales='@json($receiptSalesForPrint, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                    data-broker-missing-boxes-for-receipt='@json($missingBoxesForPrint, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                >
                    <!-- Broker Header -->
                    <div class="p-3 sm:p-4 border-b border-gray-200 cursor-pointer transition-colors {{ $loop->even ? 'bg-gray-50 hover:bg-gray-100' : 'bg-white hover:bg-gray-50' }}" @click="expanded = !expanded">
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                            <!-- Broker Info -->
                            <div class="flex items-center space-x-3">
                                <div class="bg-blue-600 text-white w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold text-sm sm:text-base flex-shrink-0">
                                    {{ strtoupper(substr($broker->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base sm:text-lg font-bold text-gray-900 truncate">{{ $broker->name }}</h3>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mt-0.5 gap-0.5 sm:gap-0">
                                        @if($broker->stall_name)
                                            <span class="text-xs sm:text-sm text-gray-600 truncate">
                                                <x-heroicon-o-building-storefront class="w-3.5 h-3.5 sm:w-4 sm:h-4 inline mr-1" />
                                                {{ $broker->stall_name }}
                                            </span>
                                        @endif
                                        @if($broker->user)
                                            <span class="text-xs sm:text-sm text-gray-600 truncate">
                                                <x-heroicon-o-envelope class="w-3.5 h-3.5 sm:w-4 sm:h-4 inline mr-1" />
                                                {{ $broker->user->email }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Metrics and Actions -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-3 lg:flex-1 lg:justify-end xl:justify-end">
                                <!-- Metrics -->
                                <div class="grid grid-cols-3 gap-2 sm:flex sm:items-center sm:justify-between lg:justify-center xl:flex-1 sm:gap-2 lg:gap-3 xl:gap-6 2xl:gap-16">
                                    <!-- Total Sales -->
                                    <div class="text-center border-2 border-blue-600 bg-blue-50 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 lg:px-2 xl:px-4 sm:min-w-[80px] lg:min-w-[85px] xl:min-w-[100px]">
                                        <p class="text-[10px] sm:text-xs text-blue-700 uppercase tracking-wider mb-0.5">Sales</p>
                                        <p class="text-base sm:text-lg lg:text-lg xl:text-xl font-bold text-blue-600">{{ $broker->sales->count() }}</p>
                                    </div>

                                    <!-- Total Fishboxes -->
                                    <div class="text-center border-2 border-orange-600 bg-orange-50 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 lg:px-2 xl:px-4 sm:min-w-[80px] lg:min-w-[85px] xl:min-w-[100px]">
                                        <p class="text-[10px] sm:text-xs text-orange-700 uppercase tracking-wider mb-0.5">Fishboxes</p>
                                        <p class="text-base sm:text-lg lg:text-lg xl:text-xl font-bold text-orange-600">{{ $brokerFishBoxCount }}</p>
                                    </div>

                                    <!-- Buyers -->
                                    <div class="text-center border-2 border-green-600 bg-green-50 rounded-lg px-2 py-1.5 sm:px-3 sm:py-2 lg:px-2 xl:px-4 sm:min-w-[80px] lg:min-w-[85px] xl:min-w-[100px]">
                                        <p class="text-[10px] sm:text-xs text-green-700 uppercase tracking-wider mb-0.5">Buyers</p>
                                        <p class="text-base sm:text-lg lg:text-lg xl:text-xl font-bold text-green-600">{{ $brokerBuyerCount }}</p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-center sm:justify-start space-x-2 sm:ml-2 lg:ml-1 xl:ml-4 flex-shrink-0">
                                    <button @click.stop="printBrokerSales({{ $broker->id }}, @js($broker->name), @js($broker->stall_name ?? ''))"
                                            class="text-green-600 hover:text-green-800 transition-colors p-1.5 sm:p-2 hover:bg-green-50 rounded-lg"
                                            title="Print Daily Receipt">
                                        <x-heroicon-o-printer class="w-6 h-6 sm:w-6 sm:h-6 lg:w-7 lg:h-7" />
                                    </button>

                                    <div class="text-blue-600 p-1.5">
                                        <x-heroicon-o-chevron-down class="w-5 h-5 transition-transform duration-200" x-bind:class="{ 'rotate-180': expanded }" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Details (Collapsible) -->
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         class="overflow-x-auto"
                         x-data="{ hoveredSale: null }">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-tight sm:tracking-wider whitespace-nowrap">
                                        <div class="flex items-center space-x-1">
                                            <x-heroicon-o-calendar class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
                                            <span>Date</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-tight sm:tracking-wider whitespace-nowrap">
                                        <div class="flex items-center space-x-1">
                                            <x-heroicon-o-user class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
                                            <span>Buyer</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-tight sm:tracking-wider whitespace-nowrap">
                                        <div class="flex items-center space-x-1">
                                            <x-heroicon-o-cube class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
                                            <span>Fish</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-tight sm:tracking-wider whitespace-nowrap">
                                        <div class="flex items-center space-x-1">
                                            <x-heroicon-o-hashtag class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
                                            <span>Qty</span>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-tight sm:tracking-wider whitespace-nowrap">
                                        <div class="flex items-center space-x-1">
                                            <x-heroicon-o-archive-box class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
                                            <span class="hidden sm:inline">Fish Boxes</span>
                                            <span class="sm:hidden">Boxes</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($broker->sales as $saleIndex => $sale)
                                    @foreach($sale->salesDetails as $detailIndex => $detail)
                                        <tr @mouseenter="hoveredSale = {{ $saleIndex }}"
                                            @mouseleave="hoveredSale = null"
                                            :class="hoveredSale === {{ $saleIndex }} ? 'bg-gray-100' : '{{ $saleIndex % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}'"
                                            class="transition-colors">
                                            @if($detailIndex === 0)
                                                <td rowspan="{{ $sale->salesDetails->count() }}" class="px-2 sm:px-6 py-2 sm:py-4 text-[10px] sm:text-sm text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                                    <div class="flex flex-col">
                                                        <span class="font-medium">{{ \Carbon\Carbon::parse($sale->sales_date)->format('M d, Y') }}</span>
                                                    </div>
                                                </td>
                                                <td rowspan="{{ $sale->salesDetails->count() }}" class="px-2 sm:px-6 py-2 sm:py-4 text-[10px] sm:text-sm text-gray-900 border-r border-gray-200">
                                                    <div class="max-w-[80px] sm:max-w-none truncate">{{ $sale->buyer_name }}</div>
                                                </td>
                                            @endif
                                            <td class="px-2 sm:px-6 py-2 sm:py-4 text-[10px] sm:text-sm font-medium text-gray-900">
                                                <div class="max-w-[80px] sm:max-w-none truncate">{{ $detail->item }}</div>
                                            </td>
                                            <td class="px-2 sm:px-6 py-2 sm:py-4 text-[10px] sm:text-sm text-gray-900 text-center">
                                                {{ $detail->quantity }}
                                            </td>
                                            <td class="px-2 sm:px-6 py-2 sm:py-4 text-[10px] sm:text-sm text-gray-900">
                                                @php
                                                    $fishBoxes = $detail->fishBoxes();
                                                @endphp
                                                @if($fishBoxes->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($fishBoxes as $fishBox)
                                                            <span class="inline-flex items-center bg-gray-100 border border-gray-300 rounded px-1 sm:px-2 py-0.5 text-[9px] sm:text-xs font-mono whitespace-nowrap">
                                                                {{ $fishBox->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 italic text-[9px] sm:text-xs">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($brokersWithSales->hasPages())
            <div class="mt-8">
                {{ $brokersWithSales->appends(request()->query())->links('components.pagination') }}
            </div>
        @endif
    @endif
</div>

<script src="{{ asset('js/broker-sales-print.js') }}?v={{ filemtime(public_path('js/broker-sales-print.js')) }}"></script>
