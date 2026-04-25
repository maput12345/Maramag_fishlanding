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
            $selectedFishTypeForBulkQr ? 'Fish Name: ' . $selectedFishTypeForBulkQr->name : null,
        ])->filter()->implode(' | ');
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 space-y-4 sm:space-y-0">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Fish Boxes List</h2>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button"
                    class="bulk-qr-print-btn w-full sm:w-auto bg-slate-700 hover:bg-slate-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center space-x-2"
                    title="Print all fish boxes that match the current filters"
                    data-bulk-qr-source="bulk-qr-boxes-data"
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
                      data-confirm-message="This will change all returned fish boxes back to stock. Continue?"
                      class="inline">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center space-x-2">
                        <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                        <span class="hidden sm:inline">Return to Stock</span>
                        <span class="sm:hidden">Return to Stock</span>
                    </button>
                </form>
                @if(($bulkRestockEligibleCount ?? 0) > 0)
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'bulk-restock']) }}"
                       class="w-full sm:w-auto bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center space-x-2">
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        <span class="hidden sm:inline">Bulk Assign / Daily Restock</span>
                        <span class="sm:hidden">Daily Restock</span>
                        <span class="inline-flex items-center justify-center rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold">
                            {{ $bulkRestockEligibleCount }}
                        </span>
                    </a>
                @else
                    <button type="button"
                            disabled
                            class="w-full sm:w-auto text-slate-500 px-4 py-2 rounded-lg text-sm font-medium shadow-sm flex items-center justify-center space-x-2 cursor-not-allowed"
                            style="background: #e5e7eb;">
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        <span class="hidden sm:inline">Bulk Assign / Daily Restock</span>
                        <span class="sm:hidden">Daily Restock</span>
                        <span class="inline-flex items-center justify-center rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500">0</span>
                    </button>
                @endif
                <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'create']) }}"
                   class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center space-x-2">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span class="hidden sm:inline">Add Stock</span>
                    <span class="sm:hidden">Add Stock</span>
                </a>
            @endunless
        </div>
    </div>

    <script id="bulk-qr-boxes-data" type="application/json">{!! json_encode($bulkQrFishBoxes ?? collect(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script id="fish-box-default-cost-map" type="application/json">{!! json_encode($fishTypeDefaultCosts ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-6 summary-strip-wrap">
        <div class="summary-strip summary-strip--four">
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Boxes</p>
                <p class="summary-stat-value text-gray-900">{{ number_format($fishBoxSummary['total'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">In Stock</p>
                <p class="summary-stat-value text-green-600">{{ number_format($fishBoxSummary['in_stock'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sold</p>
                <p class="summary-stat-value text-blue-600">{{ number_format($fishBoxSummary['sold'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Returned</p>
                <p class="summary-stat-value text-yellow-600">{{ number_format($fishBoxSummary['returned'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Fish Box Modal -->
    @if(in_array(request('modal'), ['create', 'edit', 'bulk-restock'], true) && $brokerViewReadOnly)
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
                <p class="text-sm text-gray-600">
                    This broker workspace is currently in read-only mode. Enable support actions first if you need to add, update, or daily restock fish box stock for this broker.
                </p>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
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
    @elseif(request('modal') === 'bulk-restock')
        @php
            $oldRestockBoxIds = collect(old('fish_box_ids', []))
                ->map(fn ($value) => (string) $value)
                ->all();
        @endphp

        <x-app-modal
            title="Bulk Assign / Daily Restock"
            subtitle="Select reusable boxes, choose today's fish name, and auto-fill the daily cost from Fish Prices when available."
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
                            Fish Name <span class="text-red-500">*</span>
                        </label>
                        <select id="bulk_restock_fish_type_id"
                                name="fish_type_id"
                                required
                                data-fish-type-select
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Select Fish Name</option>
                            @foreach($fishTypes as $fishType)
                                <option value="{{ $fishType->id }}"
                                    {{ (string) old('fish_type_id') === (string) $fishType->id ? 'selected' : '' }}>
                                    {{ $fishType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('fish_type_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bulk_restock_cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Cost Price
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-gray-500">PHP</span>
                            <input type="number"
                                   id="bulk_restock_cost_price"
                                   name="cost_price"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('cost_price') }}"
                                   data-cost-input
                                   class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Auto-filled from Fish Prices when available">
                        </div>
                        <p class="mt-1 text-xs text-gray-500" data-default-cost-note>
                            Select a fish name to load the daily default cost. You can still enter a manual cost if needed.
                        </p>
                        @error('cost_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex flex-col gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Reusable boxes for today</p>
                            <p class="text-xs text-blue-700">
                                Only boxes currently marked In Stock or Returned can be reassigned in bulk.
                            </p>
                        </div>
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
                                            {{ $restockFishBox->cost_price !== null ? 'PHP ' . number_format((float) $restockFishBox->cost_price, 2) : 'Not set' }}
                                        </span>
                                        <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $restockFishBox->status === 'Returned' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $restockFishBox->status }}
                                        </span>
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
                       class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            {{ ($bulkRestockEligibleBoxes ?? collect())->isEmpty() ? 'disabled' : '' }}
                            class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-green-300 sm:w-auto">
                        Apply Daily Restock
                    </button>
                </div>
            </form>
        </x-app-modal>
    @elseif(request('modal') === 'create' || request('modal') === 'edit')
        <x-app-modal
            :title="request('modal') === 'edit' ? 'Edit Fish Box' : 'Add Stock'"
            :subtitle="request('modal') === 'edit' ? 'Update the fish box details and current status. Cost can auto-fill from Fish Prices, but manual edit stays available as backup.' : 'Register new reusable stock with fish name and cost price.'"
            :close-url="route('broker.inventory.index', ['tab' => 'fishBoxes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
                    <x-heroicon-o-archive-box class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ request('modal') === 'edit' ? route('broker.fish-boxes.update', request('edit', 0)) : route('broker.fish-boxes.store') }}"
                  method="POST"
                  class="space-y-6"
                  data-cost-autofill-form>
                @csrf
                @if(request('modal') === 'edit')
                    @method('PUT')
                @endif

                <div>
                    <label for="fish_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Fish Name <span class="text-red-500">*</span>
                    </label>
                    <select id="fish_type_id"
                            name="fish_type_id"
                            required
                            data-fish-type-select
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Select Fish Name</option>
                        @foreach($fishTypes as $fishType)
                            <option value="{{ $fishType->id }}"
                                {{ (string) old('fish_type_id', request('modal') === 'edit' && $editingFishBox ? $editingFishBox->fish_type_id : '') === (string) $fishType->id ? 'selected' : '' }}>
                                {{ $fishType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('fish_type_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Cost Price
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-gray-500">PHP</span>
                        <input type="number"
                               id="cost_price"
                               name="cost_price"
                               min="0"
                               step="0.01"
                               data-cost-input
                               value="{{ old('cost_price', request('modal') === 'edit' && $editingFishBox ? $editingFishBox->cost_price : '') }}"
                               class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Auto-filled from Fish Prices when available">
                    </div>
                    <p class="mt-1 text-xs text-gray-500" data-default-cost-note>
                        Select a fish name to load the daily default cost. You can still enter a manual cost if needed.
                    </p>
                    @error('cost_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if(request('modal') === 'edit')
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Select Status</option>
                            @foreach($fishBoxStatuses as $status)
                                <option value="{{ $status }}"
                                    {{ old('status', $editingFishBox?->status) === $status ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
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
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
                        class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto">
                        {{ request('modal') === 'edit' ? 'Update Fish Box' : 'Add Stock' }}
                    </button>
                </div>
            </form>
        </x-app-modal>
    @endif

    <!-- Fish Boxes Filters -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <form method="GET" action="{{ route('broker.inventory.index') }}" x-data="{
            search: '{{ request('search') }}',
            status: '{{ request('status') }}',
            fishType: '{{ request('fish_type') }}'
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
                            placeholder="Search fish box name or fish name..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="status-field">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" x-model="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fish Name</label>
                    <select name="fish_type" x-model="fishType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Fish Names</option>
                        @foreach($fishTypes as $fishType)
                            <option value="{{ $fishType->id }}" {{ request('fish_type') == $fishType->id ? 'selected' : '' }}>
                                {{ $fishType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="buttons-field flex justify-end space-x-2">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes']) }}"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Clear
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
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
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-archive-box class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="qr-code-btn text-gray-400 hover:text-green-600 transition-colors"
                                title="View QR Code"
                                data-qr-data="{{ $fishBox->qr_code }}"
                                data-fish-box-name="{{ $fishBox->name }}">
                            <x-heroicon-o-qr-code class="w-6 h-6" />
                        </button>
                        @if(!$brokerViewReadOnly && $fishBox->canBeEdited())
                            <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'edit', 'edit' => $fishBox->id]) }}"
                                class="text-gray-400 hover:text-green-600 transition-colors"
                                title="Edit Fish Box">
                                <x-heroicon-o-pencil-square class="w-6 h-6" />
                            </a>
                        @endif
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
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $fishBox->cost_price !== null ? 'PHP ' . number_format((float) $fishBox->cost_price, 2) : 'Not set' }}
                    </p>
                </div>
                <div class="mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $fishBox->status === 'In Stock' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $fishBox->status === 'Sold' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $fishBox->status === 'Returned' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $fishBox->status === 'Missing' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ $fishBox->status }}
                    </span>
                </div>
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
                        {{ $brokerViewReadOnly ? 'No fish boxes are available for this broker right now.' : 'Get started by adding your first stock.' }}
                    </p>
                    @unless($brokerViewReadOnly)
                        <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'create']) }}"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center space-x-2">
                            <x-heroicon-o-plus class="w-5 h-5" />
                            <span>Add Stock</span>
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
                            noteElement.textContent = 'Select a fish name to load the daily default cost. You can still enter a manual cost if needed.';
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

                            noteElement.textContent = `Default cost from Fish Prices: PHP ${formatCost(defaultCost)}. You can still override it manually.`;
                            return;
                        }

                        if (costInput.dataset.autofilled === 'true') {
                            costInput.value = '';
                        }

                        costInput.dataset.autofilled = 'false';
                        noteElement.textContent = 'No default cost is set for this fish name in Fish Prices yet. Enter a manual cost price.';
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
        });
    </script>

</div>
