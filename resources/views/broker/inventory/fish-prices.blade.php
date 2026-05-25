<div>
    @php
$brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
            ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
            : false;
        $fishPriceUpdateUrlTemplate = route('broker.fish-prices.update', ['id' => '__ID__']);
        $fishPriceHistoryPayload = $brokerFishTypes->getCollection()
            ->filter(fn ($assignment) => $assignment->prices->isNotEmpty())
            ->mapWithKeys(fn ($assignment) => [
                (string) $assignment->id => [
                    'name' => $assignment->display_name ?? 'Unknown Fish',
                    'records' => $assignment->prices->map(fn ($priceRecord) => [
                        'date' => optional($priceRecord->price_date)->format('Y-m-d'),
                        'date_label' => optional($priceRecord->price_date)->format('M d, Y') ?? 'Not set',
                        'price' => '₱' . number_format((float) $priceRecord->price, 2),
                        'cost' => $priceRecord->default_cost_price !== null
                            ? '₱' . number_format((float) $priceRecord->default_cost_price, 2)
                            : 'Not set',
                    ])->values(),
                ],
            ]);
    @endphp
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Fish Price List</h2>
        </div>
        @unless($brokerViewReadOnly)
            <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices', 'modal' => 'create']) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <x-heroicon-o-plus class="w-4 h-4" />
                Set Price
            </a>
        @endunless
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-500">No. of Fish</p>
            <p class="summary-stat-value text-gray-900">{{ number_format($priceSummary['assigned']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-500">Priced Fishes</p>
            <p class="summary-stat-value text-green-600">{{ number_format($priceSummary['priced']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-500">Unpriced Fishes</p>
            <p class="summary-stat-value text-orange-600">{{ number_format($priceSummary['unpriced']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-500">Last Price Update</p>
            <p class="mt-2 text-lg font-bold text-gray-900">
                {{ $priceSummary['latest_price_date'] ? $priceSummary['latest_price_date']->format('M d, Y') : 'Not set' }}
            </p>
        </div>
    </div>

    @if((request('modal') === 'create' || request('modal') === 'edit') && $brokerViewReadOnly)
        <x-app-modal
            title="Support Actions Required"
            subtitle="Broker pricing is read-only until an admin explicitly enables support actions."
            :close-url="route('broker.inventory.index', ['tab' => 'fishPrices'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="space-y-6 py-2">
                <p class="text-sm text-gray-600">
                    This broker workspace is currently in read-only mode. Enable support actions first if you need to add, update, or remove fish prices for this broker.
                </p>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices']) }}"
                       class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Back
                    </a>
                    <form method="POST" action="{{ route('admin.broker-view.support.enable') }}" class="sm:w-auto">
                        @csrf
                        <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-amber-700 sm:w-auto">
                            Enable Support Actions
                        </button>
                    </form>
                </div>
            </div>
        </x-app-modal>
    @elseif(request('modal') === 'create' || request('modal') === 'edit')
        <x-app-modal
            :title="request('modal') === 'edit' ? 'Update Fish Price' : 'Set Price'"
            :subtitle="request('modal') === 'edit' ? 'Adjust today\'s selling price and stock cost for this broker fish.' : 'Set today\'s selling price and stock cost for an assigned fish.'"
            :close-url="route('broker.inventory.index', ['tab' => 'fishPrices'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-sm">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ request('modal') === 'edit' && $editingBrokerFishType ? route('broker.fish-prices.update', $editingBrokerFishType->id) : route('broker.fish-prices.store') }}"
                  method="POST"
                  class="space-y-6"
                  data-price-form>
                @csrf
                @if(request('modal') === 'edit')
                    @method('PUT')
                @endif

                @if(request('modal') === 'create')
                    @php
                        $selectedPricingAssignment = old('broker_fish_type_id')
                            ? $pricingAssignments->firstWhere('id', (int) old('broker_fish_type_id'))
                            : null;
                    @endphp
                    <div data-price-fish-combobox class="relative">
                        <label for="broker_fish_type_search" class="block text-sm font-medium text-gray-700 mb-2">
                            Fish <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden"
                               id="broker_fish_type_id"
                               name="broker_fish_type_id"
                               value="{{ old('broker_fish_type_id') }}"
                               data-price-fish-value>
                        <input type="search"
                               id="broker_fish_type_search"
                               data-price-fish-search
                               autocomplete="off"
                               value="{{ $selectedPricingAssignment?->display_name ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               placeholder="Search and select fish type..."
                               required>
                        <div class="absolute left-0 right-0 z-50 mt-1 hidden max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl ring-1 ring-black/5"
                             style="z-index: 80;"
                             data-price-fish-list>
                            @foreach($pricingAssignments as $assignment)
                                <button type="button"
                                        class="block w-full px-4 py-2.5 text-left text-sm text-gray-700 transition-colors hover:bg-green-50 hover:text-green-700"
                                        data-price-fish-option
                                        data-value="{{ $assignment->id }}"
                                        data-label="{{ $assignment->display_name ?? 'Unknown Fish' }}"
                                        data-search="{{ \Illuminate\Support\Str::lower(($assignment->display_name ?? 'Unknown Fish') . ' ' . $assignment->id) }}">
                                    {{ $assignment->display_name ?? 'Unknown Fish' }}
                                    @if($assignment->latestPrice)
                                        - Current: ₱{{ number_format((float) $assignment->latestPrice->price, 2) }}
                                    @endif
                                </button>
                            @endforeach
                            <p class="hidden px-4 py-3 text-sm text-gray-500" data-price-fish-empty>
                                No fish type found.
                            </p>
                        </div>
                        @error('broker_fish_type_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @elseif($editingBrokerFishType)
                    <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-green-700">Fish</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ $editingBrokerFishType->display_name ?? 'Unknown Fish' }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                            Price <span class="text-red-500">*</span>
                        </label>
                        <div class="currency-input-group">
                            <span class="currency-input-symbol">₱</span>
                            <input type="text"
                                   id="price"
                                   name="price"
                                   value="{{ old('price', $editingBrokerFishType?->latestPrice?->price) }}"
                                   class="currency-input-field"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   data-currency-format
                                   placeholder="0.00"
                                   required>
                        </div>
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="default_cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Stock Cost
                        </label>
                        <div class="currency-input-group">
                            <span class="currency-input-symbol">₱</span>
                            <input type="text"
                                   id="default_cost_price"
                                   name="default_cost_price"
                                   value="{{ old('default_cost_price', $editingBrokerFishType?->latestPrice?->default_cost_price) }}"
                                   class="currency-input-field"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   data-currency-format
                                   placeholder="0.00">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Used as the stock cost for daily restock and cost reporting.
                        </p>
                        @error('default_cost_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="price_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="price_date"
                               name="price_date"
                               value="{{ old('price_date', optional($editingBrokerFishType?->latestPrice?->price_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                               required>
                        @error('price_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices']) }}"
                       class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto">
                        {{ request('modal') === 'edit' ? 'Save Price' : 'Add Price' }}
                    </button>
                </div>
            </form>
        </x-app-modal>
    @endif

    @if(request('modal') === 'history')
        <x-app-modal
            title="Price History"
            :subtitle="$historyBrokerFishType ? 'Previous prices for ' . ($historyBrokerFishType->display_name ?? 'this fish') . '.' : 'No fish price history was found.'"
            :close-url="route('broker.inventory.index', ['tab' => 'fishPrices'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl shadow-sm"
                     style="background: #2563eb; color: #ffffff;">
                    <x-heroicon-o-clock class="h-5 w-5" />
                </div>
            </x-slot:icon>

            @if($historyBrokerFishType)
                <form method="GET" action="{{ route('broker.inventory.index') }}" class="mb-4">
                    <input type="hidden" name="tab" value="fishPrices">
                    <input type="hidden" name="modal" value="history">
                    <input type="hidden" name="history" value="{{ $historyBrokerFishType->id }}">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <div class="flex-1">
                            <input type="date"
                                   name="history_date"
                                   value="{{ request('history_date') }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="filter-action-group">
                            <button type="submit"
                                    class="btn-search">
                                Search
                            </button>
                            @if(request()->filled('history_date'))
                                <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices', 'modal' => 'history', 'history' => $historyBrokerFishType->id]) }}"
                                   class="btn-clear">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($historyBrokerFishType->prices as $priceRecord)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                            {{ $priceRecord->price_date?->format('M d, Y') ?? 'Not set' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold tabular-nums text-gray-900">
                                            ₱{{ number_format((float) $priceRecord->price, 2) }}
                                            <span class="sr-only">PHP {{ number_format((float) $priceRecord->price, 2) }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-900">
                                            @if($priceRecord->default_cost_price !== null)
                                                ₱{{ number_format((float) $priceRecord->default_cost_price, 2) }}
                                                <span class="sr-only">PHP {{ number_format((float) $priceRecord->default_cost_price, 2) }}</span>
                                            @else
                                                <span class="text-gray-400">Not set</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                                            {{ request()->filled('history_date') ? 'No price history matched that date.' : 'No price history yet.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    The selected fish price history could not be found.
                </div>
            @endif

            <div class="mt-6 flex justify-end border-t border-gray-100 pt-5">
                <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices']) }}"
                   class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Close
                </a>
            </div>
        </x-app-modal>
    @endif

    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <form method="GET" action="{{ route('broker.inventory.index') }}" x-data="{ search: @js((string) request('search', '')) }">
            <input type="hidden" name="tab" value="fishPrices">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               x-model="search"
                               placeholder="Search fish..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>
                <div class="filter-action-group">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices']) }}"
                       class="btn-clear">
                        Clear
                    </a>
                    <button type="submit"
                            class="btn-search">
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Showing {{ $brokerFishTypes->firstItem() ?? 0 }} to {{ $brokerFishTypes->lastItem() ?? 0 }} of {{ $brokerFishTypes->total() }} assigned fish
            @if(request()->has('search'))
                <span class="text-green-600">(filtered)</span>
            @endif
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <script id="fish-price-history-data" type="application/json">{!! json_encode($fishPriceHistoryPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Price</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($brokerFishTypes as $assignment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-600 rounded-lg flex items-center justify-center">
                                        <x-heroicon-o-tag class="w-5 h-5 text-white" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $assignment->display_name ?? 'Unknown Fish' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums text-gray-900">
                                @if($assignment->latestPrice)
                                    <span class="font-semibold">₱{{ number_format((float) $assignment->latestPrice->price, 2) }}</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums text-gray-900">
                                @if($assignment->latestPrice?->default_cost_price !== null)
                                    <span class="font-semibold">₱{{ number_format((float) $assignment->latestPrice->default_cost_price, 2) }}</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $assignment->latestPrice?->price_date?->format('M d, Y') ?? 'Not set' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($assignment->latestPrice)
                                    <x-status-badge status="Available" label="Priced" size="sm" />
                                @else
                                    <x-status-badge status="Pending" label="Needs Price" size="sm" />
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @if((int) $assignment->prices_count > 0)
                                        <button type="button"
                                           data-fish-price-history-open
                                           data-assignment-id="{{ $assignment->id }}"
                                           class="transition-colors"
                                           style="color: #2563eb;"
                                           title="View price history">
                                            <x-heroicon-o-clock class="w-6 h-6" />
                                        </button>
                                    @else
                                        <button type="button" class="text-gray-400 cursor-not-allowed" title="No price history yet">
                                            <x-heroicon-o-clock class="w-6 h-6" />
                                        </button>
                                    @endif
                                    @unless($brokerViewReadOnly)
                                        <button type="button"
                                           data-fish-price-edit-open
                                           data-assignment-id="{{ $assignment->id }}"
                                           data-fish-name="{{ $assignment->display_name ?? 'Unknown Fish' }}"
                                           data-price="{{ $assignment->latestPrice?->price }}"
                                           data-cost="{{ $assignment->latestPrice?->default_cost_price }}"
                                           data-date="{{ $assignment->latestPrice?->price_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}"
                                           class="text-green-600 hover:text-green-900 transition-colors"
                                           title="{{ $assignment->latestPrice ? 'Edit price' : 'Set price' }}">
                                            <x-heroicon-o-pencil-square class="w-6 h-6" />
                                        </button>
                                        @if($assignment->latestPrice)
                                            <form action="{{ route('broker.fish-prices.destroy', $assignment->id) }}"
                                                  method="POST"
                                                  class="inline"
                                                  data-swal="delete"
                                                  data-record-name="{{ $assignment->display_name ?? 'fish price' }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Remove price">
                                                    <x-heroicon-o-trash class="w-6 h-6" />
                                                </button>
                                            </form>
                                        @endif
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No fish assignments found. Add fish first before setting prices.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($brokerFishTypes->hasPages())
        <div class="mt-8">
            {{ $brokerFishTypes->appends(request()->query())->links('components.pagination') }}
        </div>
    @endif

    <div id="fish-price-history-modal"
         class="fixed inset-0 z-[9999] hidden overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
            <button type="button"
                    class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]"
                    data-fish-price-history-close
                    aria-label="Close price history"></button>
            <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm">
                            <x-heroicon-o-clock class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-950">Price History</h3>
                            <p class="mt-1 text-sm text-slate-500" data-fish-price-history-subtitle>Previous price records.</p>
                        </div>
                    </div>
                    <button type="button"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            data-fish-price-history-close
                            aria-label="Close price history">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="date"
                               class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                               data-fish-price-history-date>
                        <button type="button" class="btn-clear" data-fish-price-history-clear>
                            Clear
                        </button>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Price</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white" data-fish-price-history-rows></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-5">
                        <button type="button"
                                class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                data-fish-price-history-close>
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="fish-price-edit-modal"
         class="fixed inset-0 z-[9999] hidden overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
            <button type="button"
                    class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]"
                    data-fish-price-edit-close
                    aria-label="Close edit price"></button>
            <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-sm">
                            <x-heroicon-o-currency-dollar class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-950">Update Fish Price</h3>
                            <p class="mt-1 text-sm text-slate-500">Adjust today's selling price and stock cost for this broker fish.</p>
                        </div>
                    </div>
                    <button type="button"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            data-fish-price-edit-close
                            aria-label="Close edit price">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <form method="POST" class="space-y-6 px-6 py-5" data-fish-price-edit-form>
                    @csrf
                    @method('PUT')
                    <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-green-700">Fish</p>
                        <p class="mt-1 text-base font-semibold text-gray-900" data-fish-price-edit-name></p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="fish-price-edit-price" class="block text-sm font-medium text-gray-700 mb-2">
                                Price <span class="text-red-500">*</span>
                            </label>
                            <div class="currency-input-group">
                                <span class="currency-input-symbol">₱</span>
                                <input type="text"
                                       id="fish-price-edit-price"
                                       name="price"
                                       class="currency-input-field"
                                       inputmode="decimal"
                                       autocomplete="off"
                                       data-currency-format
                                       placeholder="0.00"
                                       required>
                            </div>
                        </div>
                        <div>
                            <label for="fish-price-edit-cost" class="block text-sm font-medium text-gray-700 mb-2">
                                Stock Cost
                            </label>
                            <div class="currency-input-group">
                                <span class="currency-input-symbol">₱</span>
                                <input type="text"
                                       id="fish-price-edit-cost"
                                       name="default_cost_price"
                                       class="currency-input-field"
                                       inputmode="decimal"
                                       autocomplete="off"
                                       data-currency-format
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <label for="fish-price-edit-date" class="block text-sm font-medium text-gray-700 mb-2">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="fish-price-edit-date"
                                   name="price_date"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                   required>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <button type="button"
                                class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
                                data-fish-price-edit-close>
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto">
                            Save Price
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const initializeFishPriceModals = function () {
            const appendToBody = (modal) => {
                if (modal && modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
            };

            const lockPage = () => {
                document.documentElement.classList.add('modal-scroll-lock');
                document.body.classList.add('modal-scroll-lock');
            };

            const unlockPage = () => {
                document.documentElement.classList.remove('modal-scroll-lock');
                document.body.classList.remove('modal-scroll-lock');
            };

            const historyModal = document.getElementById('fish-price-history-modal');
            const historyDataElement = document.getElementById('fish-price-history-data');
            const historyRows = historyModal?.querySelector('[data-fish-price-history-rows]');
            const historyDateInput = historyModal?.querySelector('[data-fish-price-history-date]');
            const historySubtitle = historyModal?.querySelector('[data-fish-price-history-subtitle]');
            const historyData = historyDataElement ? JSON.parse(historyDataElement.textContent || '{}') : {};
            let activeHistoryRecords = [];

            appendToBody(historyModal);

            const priceFishCombobox = document.querySelector('[data-price-fish-combobox]');
            const priceFishSearch = document.querySelector('[data-price-fish-search]');
            const priceFishValue = document.querySelector('[data-price-fish-value]');
            const priceFishList = document.querySelector('[data-price-fish-list]');
            const priceFishOptions = Array.from(document.querySelectorAll('[data-price-fish-option]'));
            const priceFishEmpty = document.querySelector('[data-price-fish-empty]');
            const priceForm = document.querySelector('[data-price-form]');
            const currencyInputs = Array.from(document.querySelectorAll('[data-currency-format]'));

            const normalizeCurrencyInput = (value) => String(value || '')
                .replace(/,/g, '')
                .replace(/[^\d.]/g, '')
                .replace(/(\..*)\./g, '$1');

            const formatCurrencyInput = (input) => {
                const normalized = normalizeCurrencyInput(input.value);

                if (normalized === '') {
                    input.value = '';
                    return;
                }

                const [wholePart, decimalPart] = normalized.split('.');
                const formattedWhole = wholePart === ''
                    ? ''
                    : Number(wholePart).toLocaleString('en-US');

                input.value = decimalPart !== undefined
                    ? `${formattedWhole}.${decimalPart.slice(0, 2)}`
                    : formattedWhole;
            };

            currencyInputs.forEach((input) => {
                formatCurrencyInput(input);
                input.addEventListener('input', () => formatCurrencyInput(input));
            });

            if (priceFishCombobox && priceFishSearch && priceFishValue && priceFishList) {
                const syncPriceFishValidity = () => {
                    priceFishSearch.setCustomValidity(priceFishValue.value ? '' : 'Select a fish type from the list.');
                };

                const closePriceFishList = () => {
                    priceFishList.classList.add('hidden');
                };

                const openPriceFishList = () => {
                    priceFishList.classList.remove('hidden');
                };

                const filterPriceFishOptions = (shouldOpen = true) => {
                    const query = priceFishSearch.value.trim().toLowerCase();
                    let visibleCount = 0;

                    priceFishOptions.forEach((option) => {
                        const matches = query === ''
                            || (option.dataset.search || '').includes(query)
                            || option.textContent.toLowerCase().includes(query);

                        option.classList.toggle('hidden', !matches);
                        visibleCount += matches ? 1 : 0;
                    });

                    priceFishEmpty?.classList.toggle('hidden', visibleCount > 0);

                    if (shouldOpen) {
                        openPriceFishList();
                    }
                };

                const selectPriceFish = (option) => {
                    priceFishValue.value = option.dataset.value || '';
                    priceFishSearch.value = option.dataset.label || option.textContent.trim();
                    syncPriceFishValidity();
                    closePriceFishList();
                };

                priceFishSearch.addEventListener('focus', () => filterPriceFishOptions(true));
                priceFishSearch.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closePriceFishList();
                        priceFishSearch.blur();
                    }
                });
                priceFishSearch.addEventListener('input', () => {
                    priceFishValue.value = '';
                    syncPriceFishValidity();
                    filterPriceFishOptions(true);
                });

                priceFishList.addEventListener('mousedown', (event) => {
                    if (event.target === priceFishList) {
                        closePriceFishList();
                    }
                });

                priceFishOptions.forEach((option) => {
                    option.addEventListener('click', () => selectPriceFish(option));
                });

                document.addEventListener('mousedown', (event) => {
                    if (!priceFishCombobox.contains(event.target)) {
                        closePriceFishList();
                    }
                }, true);

                document.addEventListener('focusin', (event) => {
                    if (!priceFishCombobox.contains(event.target)) {
                        closePriceFishList();
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!priceFishCombobox.contains(event.target)) {
                        closePriceFishList();
                    }
                });

                priceForm?.addEventListener('submit', () => {
                    syncPriceFishValidity();
                });

                syncPriceFishValidity();
                filterPriceFishOptions(false);
            }

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const renderHistoryRows = () => {
                if (!historyRows) {
                    return;
                }

                const selectedDate = historyDateInput?.value || '';
                const records = selectedDate
                    ? activeHistoryRecords.filter((record) => record.date === selectedDate)
                    : activeHistoryRecords;

                if (records.length === 0) {
                    historyRows.innerHTML = `
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                                ${selectedDate ? 'No price history matched that date.' : 'No price history yet.'}
                            </td>
                        </tr>
                    `;
                    return;
                }

                historyRows.innerHTML = records.map((record) => `
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">${escapeHtml(record.date_label)}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold tabular-nums text-gray-900">${escapeHtml(record.price)}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-900">${escapeHtml(record.cost)}</td>
                    </tr>
                `).join('');
            };

            const openHistoryModal = (button) => {
                const history = historyData[String(button.dataset.assignmentId)] || {};
                activeHistoryRecords = Array.isArray(history.records) ? history.records : [];
                if (historySubtitle) {
                    historySubtitle.textContent = history.name
                        ? `Previous prices for ${history.name}.`
                        : 'Previous price records.';
                }
                if (historyDateInput) {
                    historyDateInput.value = '';
                }
                renderHistoryRows();
                historyModal?.classList.remove('hidden');
                lockPage();
            };

            const closeHistoryModal = () => {
                historyModal?.classList.add('hidden');
                unlockPage();
            };

            document.querySelectorAll('[data-fish-price-history-open]').forEach((button) => {
                button.addEventListener('click', () => openHistoryModal(button));
            });

            historyModal?.querySelectorAll('[data-fish-price-history-close]').forEach((button) => {
                button.addEventListener('click', closeHistoryModal);
            });

            historyDateInput?.addEventListener('input', renderHistoryRows);
            historyModal?.querySelector('[data-fish-price-history-clear]')?.addEventListener('click', () => {
                if (historyDateInput) {
                    historyDateInput.value = '';
                }
                renderHistoryRows();
            });

            const editModal = document.getElementById('fish-price-edit-modal');
            const editForm = editModal?.querySelector('[data-fish-price-edit-form]');
            const editName = editModal?.querySelector('[data-fish-price-edit-name]');
            const editPrice = editModal?.querySelector('#fish-price-edit-price');
            const editCost = editModal?.querySelector('#fish-price-edit-cost');
            const editDate = editModal?.querySelector('#fish-price-edit-date');
            const updateUrlTemplate = @json($fishPriceUpdateUrlTemplate);

            appendToBody(editModal);

            const openEditModal = (button) => {
                if (!editModal || !editForm || !editName || !editPrice || !editCost || !editDate) {
                    return;
                }

                editForm.action = updateUrlTemplate.replace('__ID__', encodeURIComponent(button.dataset.assignmentId || ''));
                editName.textContent = button.dataset.fishName || 'Unknown Fish';
                editPrice.value = button.dataset.price || '';
                editCost.value = button.dataset.cost || '';
                formatCurrencyInput(editPrice);
                formatCurrencyInput(editCost);
                editDate.value = button.dataset.date || new Date().toISOString().slice(0, 10);
                editModal.classList.remove('hidden');
                lockPage();
                window.requestAnimationFrame(() => editPrice.focus({ preventScroll: true }));
            };

            const closeEditModal = () => {
                editModal?.classList.add('hidden');
                unlockPage();
            };

            document.querySelectorAll('[data-fish-price-edit-open]').forEach((button) => {
                button.addEventListener('click', () => openEditModal(button));
            });

            editModal?.querySelectorAll('[data-fish-price-edit-close]').forEach((button) => {
                button.addEventListener('click', closeEditModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (historyModal && !historyModal.classList.contains('hidden')) {
                    closeHistoryModal();
                }

                if (editModal && !editModal.classList.contains('hidden')) {
                    closeEditModal();
                }
            });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeFishPriceModals, { once: true });
            } else {
                initializeFishPriceModals();
            }
        })();
    </script>
</div>
