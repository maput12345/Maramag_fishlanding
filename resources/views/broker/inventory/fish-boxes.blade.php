<!-- Fish Boxes Tab Content -->
<div>
    @php
$brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
            ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
            : false;
        $selectedFishTypeForBulkQr = request()->filled('fish_type')
            ? $fishTypes->firstWhere('id', (int) request('fish_type'))
            : null;

        $bulkQrFilterSummary = collect([
            request('search') ? 'Search: ' . request('search') : null,
            request('status') ? 'Status: ' . request('status') : null,
            $selectedFishTypeForBulkQr ? 'Fish: ' . $selectedFishTypeForBulkQr->display_name : null,
        ])->filter()->implode(' | ');
        $fishBoxListQuery = array_merge(
            request()->except(['modal', 'history', 'box_history_date']),
            ['tab' => 'fishBoxes']
        );
        $fishBoxHistoryPayload = $fishBoxes->getCollection()
            ->filter(fn ($fishBox) => $fishBox->purchases->isNotEmpty())
            ->mapWithKeys(fn ($fishBox) => [
                (string) $fishBox->id => [
                    'name' => $fishBox->name,
                    'records' => $fishBox->purchases->map(fn ($stockCycle) => [
                        'date' => optional($stockCycle->purchase_date)->format('Y-m-d'),
                        'date_label' => optional($stockCycle->purchase_date)->format('M d, Y') ?? 'Not set',
                        'fish' => \App\Models\BrokerFishTypeAssignment::resolveDisplayName($fishBox->broker_id, $stockCycle->fishType) ?? 'Unassigned',
                        'cost' => $stockCycle->cost_price !== null ? '₱' . number_format((float) $stockCycle->cost_price, 2) : 'Not set',
                    ])->values(),
                ],
            ]);
    @endphp
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 space-y-4 sm:space-y-0">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Fish Boxes List</h2>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
            <label class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm sm:w-auto">
                <span class="whitespace-nowrap font-medium">QR Size</span>
                <select id="bulk-qr-size"
                        class="app-select min-w-28 border-0 bg-transparent py-0 pl-1 pr-7 text-sm font-semibold text-gray-900 focus:ring-0">
                    <option value="150">Small</option>
                    <option value="180" selected>Medium</option>
                    <option value="220">Large</option>
                </select>
            </label>
            <button type="button"
                    class="bulk-qr-print-btn app-button app-button--dark w-full sm:w-auto px-4 py-2 text-sm"
                    title="Print all fish boxes that match the current filters"
                    data-bulk-qr-source="bulk-qr-boxes-data"
                    data-bulk-qr-size-source="bulk-qr-size"
                    data-filter-summary="{{ $bulkQrFilterSummary }}"
                    @disabled(($bulkQrFishBoxes ?? collect())->isEmpty())>
                <x-heroicon-o-printer class="w-4 h-4" />
                <span class="hidden sm:inline">Print all QR Code</span>
                <span class="sm:hidden">Bulk QR</span>
                <span class="inline-flex items-center justify-center rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold">
                    {{ ($bulkQrFishBoxes ?? collect())->count() }}
                </span>
            </button>
            @unless($brokerViewReadOnly)
                <form method="POST"
                      action="{{ route('broker.fish-boxes.return-to-stock') }}"
                      data-inventory-async="return-to-stock"
                      data-confirm-message="This will clear returned boxes and mark them as unassigned. Continue?"
                      class="inline">
                    @csrf
                    <button type="submit" class="app-button app-button--primary w-full sm:w-auto px-4 py-2 text-sm">
                        <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                        <span class="hidden sm:inline">Returned Boxes</span>
                        <span class="sm:hidden">Clear Returned</span>
                    </button>
                </form>
                @if(($bulkRestockEligibleCount ?? 0) > 0)
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'bulk-restock']) }}"
                       class="app-button app-button--dark w-full sm:w-auto px-4 py-2 text-sm">
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        <span class="hidden sm:inline">Daily Restock</span>
                        <span class="sm:hidden">Daily Restock</span>
                        <span class="inline-flex items-center justify-center rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold">
                            {{ $bulkRestockEligibleCount }}
                        </span>
                    </a>
                @else
                    <button type="button"
                            disabled
                            class="app-button app-button--secondary w-full sm:w-auto px-4 py-2 text-sm"
                            style="background: #e5e7eb;">
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        <span class="hidden sm:inline">Daily Restock</span>
                        <span class="sm:hidden">Daily Restock</span>
                        <span class="inline-flex items-center justify-center rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500">0</span>
                    </button>
                @endif
                <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'create']) }}"
                   class="app-button app-button--success w-full sm:w-auto px-4 py-2 text-sm">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span class="hidden sm:inline">Create Boxes</span>
                    <span class="sm:hidden">Create Boxes</span>
                </a>
            @endunless
        </div>
    </div>

    <script id="bulk-qr-boxes-data" type="application/json">{!! json_encode($bulkQrFishBoxes ?? collect(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script id="fish-box-default-cost-map" type="application/json">{!! json_encode($fishTypeDefaultCosts ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script id="fish-box-history-data" type="application/json">{!! json_encode($fishBoxHistoryPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-6 summary-strip-wrap">
        <div class="summary-strip summary-strip--five">
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Boxes</p>
                <p class="summary-stat-value text-gray-900" data-fish-box-summary="total">{{ number_format($fishBoxSummary['total'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unassigned</p>
                <p class="summary-stat-value text-slate-600" data-fish-box-summary="unassigned">{{ number_format($fishBoxSummary['unassigned'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">In Stock</p>
                <p class="summary-stat-value text-green-600" data-fish-box-summary="in_stock">{{ number_format($fishBoxSummary['in_stock'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sold</p>
                <p class="summary-stat-value text-blue-600" data-fish-box-summary="sold">{{ number_format($fishBoxSummary['sold'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Returned</p>
                <p class="summary-stat-value text-yellow-600" data-fish-box-summary="returned">{{ number_format($fishBoxSummary['returned'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Add/Restock Fish Box Modal -->
    @if(in_array(request('modal'), ['create', 'bulk-restock'], true) && $brokerViewReadOnly)
        <x-app-modal
            title="Support Actions Required"
            subtitle="Broker inventory is read-only until an admin explicitly enables support actions."
            :close-url="route('broker.inventory.index', ['tab' => 'fishBoxes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="space-y-6 py-2">

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
                       class="app-button app-button--secondary w-full px-4 py-2.5 text-sm sm:w-auto">
                        Back
                    </a>
                    <form method="POST" action="{{ route('admin.broker-view.support.enable') }}" class="sm:w-auto">
                        @csrf
                        <button type="submit"
                                class="app-button app-button--warning w-full px-4 py-2.5 text-sm sm:w-auto">
                            Enable Support Actions
                        </button>
                    </form>
                </div>
            </div>
        </x-app-modal>
    @elseif(request('modal') === 'bulk-restock')
        @php
$oldRestockBoxIds = collect(old('fish_box_ids', []))
                ->map(fn ($value) => (string) $value)
                ->all();
        @endphp
<x-app-modal
            title="Assign / Daily Restock"
            subtitle="Select reusable boxes, choose today's fish, and auto-fill the daily cost from Fish Prices when available."
            :close-url="route('broker.inventory.index', ['tab' => 'fishBoxes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
                    <x-heroicon-o-squares-2x2 class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ route('broker.fish-boxes.bulk-restock') }}"
                  method="POST"
                  class="space-y-6"
                  data-cost-autofill-form>
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="bulk_restock_fish_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Fish <span class="text-red-500">*</span>
                        </label>
                        <select id="bulk_restock_fish_type_id"
                                name="fish_type_id"
                                required
                                data-fish-type-select
                                class="app-select w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Select Fish</option>
                            @foreach($fishTypes as $fishType)
                                <option value="{{ $fishType->id }}"
                                    {{ (string) old('fish_type_id') === (string) $fishType->id ? 'selected' : '' }}>
                                    {{ $fishType->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('fish_type_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bulk_restock_cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Cost
                        </label>
                        <div class="currency-input-group">
                            <span class="currency-input-symbol">₱</span>
                            <input type="number"
                                   id="bulk_restock_cost_price"
                                   name="cost_price"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('cost_price') }}"
                                   data-cost-input
                                   class="currency-input-field"
                                   placeholder="Auto-filled from Fish Prices when available">
                        </div>
                        <p class="mt-1 text-xs text-gray-500" data-default-cost-note>
                            Select a fish to load the daily cost. You can still enter a manual cost if needed.
                        </p>
                        @error('cost_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-col gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        @if(($bulkRestockEligibleBoxes ?? collect())->isNotEmpty())
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-blue-900">
                                <input type="checkbox"
                                       data-select-all-restock
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Select all {{ $bulkRestockEligibleBoxes->count() }} boxes</span>
                            </label>
                        @endif
                    </div>

                    @if(($bulkRestockEligibleBoxes ?? collect())->isEmpty())
                        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500">
                            No reusable fish boxes are available for daily restocking right now.
                        </div>
                    @else
                        <div class="grid max-h-80 grid-cols-1 gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                            @foreach($bulkRestockEligibleBoxes as $restockFishBox)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 px-4 py-3 transition-colors hover:border-blue-300 hover:bg-blue-50/40">
                                    <input type="checkbox"
                                           name="fish_box_ids[]"
                                           value="{{ $restockFishBox->id }}"
                                           data-restock-checkbox
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ in_array((string) $restockFishBox->id, $oldRestockBoxIds, true) ? 'checked' : '' }}>
                                    <span class="block min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $restockFishBox->name }}</span>
                                        <span class="mt-1 block text-xs text-gray-500">
                                            Current fish: {{ $restockFishBox->fish_type_name ?? 'Unassigned' }}
                                        </span>
                                        <span class="mt-1 block text-xs text-gray-500">
                                            Current cost:
                                            {{ $restockFishBox->cost_price !== null ? '₱' . number_format((float) $restockFishBox->cost_price, 2) : 'Not set' }}
                                        </span>
                                        <x-status-badge :status="$restockFishBox->status" size="sm" class="mt-2" />
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @error('fish_box_ids')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('fish_box_ids.*')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
                       class="app-button app-button--secondary w-full px-4 py-2.5 text-sm sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            {{ ($bulkRestockEligibleBoxes ?? collect())->isEmpty() ? 'disabled' : '' }}
                            class="app-button app-button--success w-full px-4 py-2.5 text-sm sm:w-auto">
                        Apply Daily Restock
                    </button>
                </div>
            </form>
        </x-app-modal>
    @elseif(request('modal') === 'create')
        <x-app-modal
            title="Create Boxes"
            subtitle="Register boxes now."
            :close-url="route('broker.inventory.index', ['tab' => 'fishBoxes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
                    <x-heroicon-o-archive-box class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ route('broker.fish-boxes.store') }}"
                  method="POST"
                  class="space-y-6">
                @csrf

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="quantity"
                           name="quantity"
                           min="1"
                           step="1"
                           required
                           value="{{ old('quantity', 1) }}"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           onkeydown="return ['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(event.code) || (event.code >= 'Digit0' && event.code <= 'Digit9') || (event.code >= 'Numpad0' && event.code <= 'Numpad9')"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                           placeholder="Enter quantity">
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="qr_code_setup" class="block text-sm font-medium text-gray-700 mb-2">
                        QR Code
                    </label>
                    <input type="text"
                           id="qr_code_setup"
                           value="Auto-generated for each box"
                           readonly
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-sm text-gray-600 focus:outline-none">
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    Newly created boxes will appear in Fish Boxes as <span class="font-semibold">Unassigned</span>. Use <span class="font-semibold">Daily Restock</span> when the broker is ready to assign fish name and cost.
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
                       class="app-button app-button--secondary w-full px-4 py-2.5 text-sm sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            class="app-button app-button--success w-full px-4 py-2.5 text-sm sm:w-auto">
                        Create Boxes
                    </button>
                </div>
            </form>
        </x-app-modal>
    @endif

    @if(request('modal') === 'history')
        <x-app-modal
            title="Fish Box History"
            :subtitle="$historyFishBox ? 'Previous stock records for ' . $historyFishBox->name . '.' : 'No fish box history was found.'"
            :close-url="route('broker.inventory.index', $fishBoxListQuery)"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl shadow-sm"
                     style="background: #2563eb; color: #ffffff;">
                    <x-heroicon-o-clock class="h-5 w-5" />
                </div>
            </x-slot:icon>

            @if($historyFishBox)
                <form method="GET" action="{{ route('broker.inventory.index') }}" class="mb-4">
                    <input type="hidden" name="tab" value="fishBoxes">
                    <input type="hidden" name="modal" value="history">
                    <input type="hidden" name="history" value="{{ $historyFishBox->id }}">
                    @foreach(request()->except(['tab', 'modal', 'history', 'box_history_date']) as $queryKey => $queryValue)
                        @if(is_scalar($queryValue) && $queryValue !== '')
                            <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                        @endif
                    @endforeach
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <div class="flex-1">
                            <input type="date"
                                   name="box_history_date"
                                   value="{{ request('box_history_date') }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="filter-action-group">
                            <button type="submit"
                                    class="btn-search">
                                Search
                            </button>
                            @if(request()->filled('box_history_date'))
                                <a href="{{ route('broker.inventory.index', array_merge($fishBoxListQuery, ['modal' => 'history', 'history' => $historyFishBox->id])) }}"
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
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fish</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($historyFishBox->purchases as $stockCycle)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-left text-sm text-gray-900">
                                            {{ $stockCycle->purchase_date?->format('M d, Y') ?? 'Not set' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900">
                                            {{ \App\Models\BrokerFishTypeAssignment::resolveDisplayName($historyFishBox->broker_id, $stockCycle->fishType) ?? 'Unassigned' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-900">
                                            @if($stockCycle->cost_price !== null)
                                                ₱{{ number_format((float) $stockCycle->cost_price, 2) }}
                                            @else
                                                <span class="text-gray-400">Not set</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                                            {{ request()->filled('box_history_date') ? 'No fish box history matched that date.' : 'No fish box history yet.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    The selected fish box history could not be found.
                </div>
            @endif

            <div class="mt-6 flex justify-end border-t border-gray-100 pt-5">
                <a href="{{ route('broker.inventory.index', $fishBoxListQuery) }}"
                   class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Close
                </a>
            </div>
        </x-app-modal>
    @endif

    <!-- Fish Boxes Filters -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <form method="GET" action="{{ route('broker.inventory.index') }}" x-data="{
            search: @js((string) request('search', '')),
            status: @js((string) request('status', '')),
            fishType: @js((string) request('fish_type', ''))
        }">
            <input type="hidden" name="tab" value="fishBoxes">
            <div class="filter-layout">
                <!-- Search Field -->
                <div class="search-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <input type="text"
                            name="search"
                            x-model="search"
                            placeholder="Search fish box name or fish..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="status-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" x-model="status" class="app-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Status</option>
                        @foreach($fishBoxStatuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Fish Type Filter -->
                <div class="fish-type-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fish</label>
                    <select name="fish_type" x-model="fishType" class="app-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Fish</option>
                        @foreach($fishTypes as $fishType)
                            <option value="{{ $fishType->id }}" {{ request('fish_type') == $fishType->id ? 'selected' : '' }}>
                                {{ $fishType->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="buttons-field filter-action-group justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
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


    <!-- Results Count -->
    <div class="mb-4">
        <p class="text-sm text-gray-600">
            <span class="hidden sm:inline">Showing {{ $fishBoxes->firstItem() ?? 0 }} to {{ $fishBoxes->lastItem() ?? 0 }} of {{ $fishBoxes->total() }} fish boxes</span>
            <span class="sm:hidden">{{ $fishBoxes->total() }} fish boxes</span>
            @if(request()->hasAny(['search', 'status', 'fish_type']))
                <span class="text-green-600">(filtered)</span>
            @endif
        </p>
    </div>

    <!-- Fish Boxes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
        @forelse($fishBoxes as $fishBox)
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow"
                 data-fish-box-card
                 data-fish-box-id="{{ $fishBox->id }}"
                 data-fish-box-status="{{ $fishBox->status }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-archive-box class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($fishBox->currentPurchase)
                            <button type="button"
                               data-fish-box-history-open
                               data-fish-box-id="{{ $fishBox->id }}"
                               class="transition-colors"
                               style="color: #2563eb;"
                               title="View fish box history">
                                <x-heroicon-o-clock class="w-6 h-6" />
                            </button>
                        @else
                            <button type="button" class="text-gray-400 cursor-not-allowed" title="No fish box history yet">
                                <x-heroicon-o-clock class="w-6 h-6" />
                            </button>
                        @endif
                        <button class="qr-code-btn text-gray-400 hover:text-green-600 transition-colors"
                                title="View QR Code"
                                data-qr-data="{{ $fishBox->qr_code }}"
                                data-fish-box-name="{{ $fishBox->name }}">
                            <x-heroicon-o-qr-code class="w-6 h-6" />
                        </button>
                        @if(!$brokerViewReadOnly && $fishBox->canBeMarkedAsMissing())
                            <form method="POST" action="{{ route('broker.fish-boxes.mark-missing', $fishBox->id) }}"
                                  data-inventory-async="mark-missing"
                                  data-confirm-message="This will mark {{ $fishBox->name }} as missing. Continue?"
                                  data-record-name="{{ $fishBox->name }}"
                                  class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="text-gray-400 hover:text-red-600 transition-colors"
                                        title="Mark as Missing">
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                                </button>
                            </form>
                        @endif
                        @if(!$brokerViewReadOnly && $fishBox->canBeReturned())
                            <form method="POST" action="{{ route('broker.fish-boxes.return', $fishBox->id) }}"
                                  data-inventory-async="return-fish-box"
                                  data-confirm-message="This will mark {{ $fishBox->name }} as returned. Continue?"
                                  data-record-name="{{ $fishBox->name }}"
                                  class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="text-gray-400 hover:text-green-600 transition-colors"
                                        title="Return Fish Box">
                                    <x-heroicon-o-arrow-uturn-left class="w-6 h-6" />
                                </button>
                            </form>
                        @endif
                        @if(!$brokerViewReadOnly && $fishBox->canBeDeleted())
                            <form action="{{ route('broker.fish-boxes.destroy', $fishBox->id) }}" method="POST" class="inline-block" data-swal="delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-gray-400 hover:text-red-600 transition-colors"
                                        title="Delete Fish Box">
                                    <x-heroicon-o-trash class="w-6 h-6" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $fishBox->name }}</h3>
                <p class="text-gray-600 text-sm mb-3">{{ $fishBox->fish_type_name ?? 'Unassigned' }}</p>
                <div class="mb-3 rounded-lg bg-gray-50 px-3 py-2">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Cost Price</p>
                    <p class="text-right text-sm font-semibold tabular-nums text-gray-900">
                        {{ $fishBox->cost_price !== null ? '₱' . number_format((float) $fishBox->cost_price, 2) : 'Not set' }}
                    </p>
                </div>
                <div class="mb-3">
                    <x-status-badge :status="$fishBox->status" size="sm" data-fish-box-status-badge />
                </div>
                @if($fishBox->status === 'Unassigned')
                @endif
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span class="font-mono text-xs">{{ Str::limit($fishBox->qr_code, 12) }}</span>
                    <span>{{ $fishBox->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <x-heroicon-o-archive-box class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No fish boxes found</h3>
                    <p class="text-gray-500 mb-6">
                        {{ $brokerViewReadOnly ? 'No fish boxes are available for this broker right now.' : 'Get started by creating your first reusable boxes.' }}
                    </p>
                    @unless($brokerViewReadOnly)
                        <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'create']) }}"
                            class="app-button app-button--success px-6 py-3">
                            <x-heroicon-o-plus class="w-5 h-5" />
                            <span>Create Boxes</span>
                        </a>
                    @endunless
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($fishBoxes->hasPages())
        <div class="mt-8">
            {{ $fishBoxes->appends(request()->query())->links('components.pagination') }}
        </div>
    @endif

    <div id="fish-box-history-modal"
         class="fixed inset-0 z-[9999] hidden overflow-y-auto"
         aria-labelledby="fish-box-history-title"
         role="dialog"
         aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
            <button type="button"
                    class="fixed inset-0 bg-slate-900/35 backdrop-blur-[2px]"
                    data-fish-box-history-close
                    aria-label="Close fish box history"></button>
            <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm">
                            <x-heroicon-o-clock class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 id="fish-box-history-title" class="text-lg font-semibold text-slate-950">Fish Box History</h3>
                            <p class="mt-1 text-sm text-slate-500" data-fish-box-history-subtitle></p>
                        </div>
                    </div>
                    <button type="button"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            data-fish-box-history-close
                            aria-label="Close fish box history">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="date"
                               class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                               data-fish-box-history-date>
                        <button type="button"
                                class="btn-clear"
                                data-fish-box-history-clear>
                            Clear
                        </button>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fish</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white" data-fish-box-history-rows></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-5">
                        <button type="button"
                                class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                data-fish-box-history-close>
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const costMapElement = document.getElementById('fish-box-default-cost-map');

            if (costMapElement) {
                const defaultCostMap = JSON.parse(costMapElement.textContent || '{}');
                const formatCost = (value) => new Intl.NumberFormat('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(Number(value));

                document.querySelectorAll('[data-cost-autofill-form]').forEach((form) => {
                    const fishTypeSelect = form.querySelector('[data-fish-type-select]');
                    const costInput = form.querySelector('[data-cost-input]');
                    const noteElement = form.querySelector('[data-default-cost-note]');

                    if (!fishTypeSelect || !costInput || !noteElement) {
                        return;
                    }

                    const updateDefaultCost = (forceFill = false) => {
                        const selectedFishTypeId = fishTypeSelect.value;

                        if (!selectedFishTypeId) {
                            noteElement.textContent = 'Select a fish to load the daily cost. You can still enter a manual cost if needed.';
                            return;
                        }

                        const defaultCost = Object.prototype.hasOwnProperty.call(defaultCostMap, selectedFishTypeId)
                            ? defaultCostMap[selectedFishTypeId]
                            : null;

                        if (defaultCost !== null && defaultCost !== '') {
                            if (forceFill || costInput.value.trim() === '' || costInput.dataset.autofilled === 'true') {
                                costInput.value = defaultCost;
                                costInput.dataset.autofilled = 'true';
                            }

                            noteElement.textContent = `Cost from Fish Prices: ₱${formatCost(defaultCost)}. You can still override it manually.`;
                            return;
                        }

                        if (costInput.dataset.autofilled === 'true') {
                            costInput.value = '';
                        }

                        costInput.dataset.autofilled = 'false';
                        noteElement.textContent = 'No cost is set for this fish in Fish Prices yet. Enter a manual cost price.';
                    };

                    fishTypeSelect.addEventListener('change', () => updateDefaultCost(true));
                    costInput.addEventListener('input', () => {
                        costInput.dataset.autofilled = 'false';
                    });

                    updateDefaultCost(false);
                });
            }

            const selectAllCheckbox = document.querySelector('[data-select-all-restock]');
            const restockCheckboxes = Array.from(document.querySelectorAll('[data-restock-checkbox]'));

            if (selectAllCheckbox && restockCheckboxes.length > 0) {
                const syncSelectAllState = () => {
                    const selectedCount = restockCheckboxes.filter((checkbox) => checkbox.checked).length;
                    selectAllCheckbox.checked = selectedCount === restockCheckboxes.length;
                    selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < restockCheckboxes.length;
                };

                selectAllCheckbox.addEventListener('change', () => {
                    restockCheckboxes.forEach((checkbox) => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });

                    syncSelectAllState();
                });

                restockCheckboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', syncSelectAllState);
                });

                syncSelectAllState();
            }

            const historyDataElement = document.getElementById('fish-box-history-data');
            const historyModal = document.getElementById('fish-box-history-modal');

            if (historyModal && historyModal.parentElement !== document.body) {
                document.body.appendChild(historyModal);
            }

            const historyRows = historyModal?.querySelector('[data-fish-box-history-rows]');
            const historySubtitle = historyModal?.querySelector('[data-fish-box-history-subtitle]');
            const historyDateInput = historyModal?.querySelector('[data-fish-box-history-date]');
            const historyClearButton = historyModal?.querySelector('[data-fish-box-history-clear]');
            let activeHistoryRecords = [];

            const historyData = historyDataElement
                ? JSON.parse(historyDataElement.textContent || '{}')
                : {};

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
                                ${selectedDate ? 'No fish box history matched that date.' : 'No fish box history yet.'}
                            </td>
                        </tr>
                    `;
                    return;
                }

                historyRows.innerHTML = records.map((record) => `
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-left text-sm text-gray-900">${escapeHtml(record.date_label)}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900">${escapeHtml(record.fish)}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-900">${escapeHtml(record.cost)}</td>
                    </tr>
                `).join('');
            };

            const openHistoryModal = (fishBoxId) => {
                if (!historyModal) {
                    return;
                }

                const history = historyData[String(fishBoxId)] || {};
                activeHistoryRecords = Array.isArray(history.records) ? history.records : [];

                if (historySubtitle) {
                    historySubtitle.textContent = history.name
                        ? `Previous stock records for ${history.name}.`
                        : 'Previous stock records.';
                }

                if (historyDateInput) {
                    historyDateInput.value = '';
                }

                renderHistoryRows();
                historyModal.classList.remove('hidden');
                document.documentElement.classList.add('modal-scroll-lock');
                document.body.classList.add('modal-scroll-lock');
            };

            const closeHistoryModal = () => {
                if (!historyModal) {
                    return;
                }

                historyModal.classList.add('hidden');
                document.documentElement.classList.remove('modal-scroll-lock');
                document.body.classList.remove('modal-scroll-lock');
            };

            document.querySelectorAll('[data-fish-box-history-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    openHistoryModal(button.dataset.fishBoxId);
                });
            });

            historyModal?.querySelectorAll('[data-fish-box-history-close]').forEach((button) => {
                button.addEventListener('click', closeHistoryModal);
            });

            historyDateInput?.addEventListener('input', renderHistoryRows);
            historyClearButton?.addEventListener('click', () => {
                if (historyDateInput) {
                    historyDateInput.value = '';
                }
                renderHistoryRows();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && historyModal && !historyModal.classList.contains('hidden')) {
                    closeHistoryModal();
                }
            });
        });
    </script>

</div>
