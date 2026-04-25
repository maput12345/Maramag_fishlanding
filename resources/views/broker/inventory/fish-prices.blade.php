<div>
    @php
        $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
            ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
            : false;
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Fish Price List</h2>
        </div>
        @unless($brokerViewReadOnly)
            <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices', 'modal' => 'create']) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <x-heroicon-o-plus class="w-4 h-4" />
                Set Fish Price
            </a>
        @endunless
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-500">No. of Fish Names</p>
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
            :title="request('modal') === 'edit' ? 'Update Fish Price' : 'Set Fish Price'"
            :subtitle="request('modal') === 'edit' ? 'Adjust the selling price and default daily cost for this broker fish name.' : 'Set a selling price and default daily cost for an assigned fish name.'"
            :close-url="route('broker.inventory.index', ['tab' => 'fishPrices'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-sm">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ request('modal') === 'edit' && $editingBrokerFishType ? route('broker.fish-prices.update', $editingBrokerFishType->id) : route('broker.fish-prices.store') }}"
                  method="POST"
                  class="space-y-6">
                @csrf
                @if(request('modal') === 'edit')
                    @method('PUT')
                @endif

                @if(request('modal') === 'create')
                    <div>
                        <label for="broker_fish_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Fish Name <span class="text-red-500">*</span>
                        </label>
                        <select id="broker_fish_type_id" name="broker_fish_type_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                required>
                            <option value="">Select Fish Name</option>
                            @foreach($pricingAssignments as $assignment)
                                <option value="{{ $assignment->id }}"
                                    {{ (string) old('broker_fish_type_id') === (string) $assignment->id ? 'selected' : '' }}>
                                    {{ $assignment->fishType?->name ?? 'Unknown Fish Name' }}
                                    @if($assignment->latestPrice)
                                        - Current: PHP {{ number_format((float) $assignment->latestPrice->price, 2) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('broker_fish_type_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @elseif($editingBrokerFishType)
                    <div class="rounded-xl border border-green-100 bg-green-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-green-700">Fish Name</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ $editingBrokerFishType->fishType?->name ?? 'Unknown Fish Name' }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                            Price <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-gray-500">PHP</span>
                            <input type="number"
                                   id="price"
                                   name="price"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('price', $editingBrokerFishType?->latestPrice?->price) }}"
                                   class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                   placeholder="0.00"
                                   required>
                        </div>
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="default_cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Default Cost Price
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-gray-500">PHP</span>
                            <input type="number"
                                   id="default_cost_price"
                                   name="default_cost_price"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('default_cost_price', $editingBrokerFishType?->latestPrice?->default_cost_price) }}"
                                   class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                   placeholder="0.00">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Used to auto-fill fish box cost when adding stock or running daily restock.
                        </p>
                        @error('default_cost_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="price_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Effective Date <span class="text-red-500">*</span>
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

    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <form method="GET" action="{{ route('broker.inventory.index') }}" x-data="{ search: '{{ request('search') }}' }">
            <input type="hidden" name="tab" value="fishPrices">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               x-model="search"
                               placeholder="Search fish name..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices']) }}"
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

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Showing {{ $brokerFishTypes->firstItem() ?? 0 }} to {{ $brokerFishTypes->lastItem() ?? 0 }} of {{ $brokerFishTypes->total() }} assigned fish names
            @if(request()->has('search'))
                <span class="text-green-600">(filtered)</span>
            @endif
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective Date</th>
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
                                        <div class="text-sm font-medium text-gray-900">{{ $assignment->fishType?->name ?? 'Unknown Fish Name' }}</div>
                                        <div class="text-sm text-gray-500">Broker fish name assignment #{{ $assignment->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($assignment->latestPrice)
                                    <span class="font-semibold">PHP {{ number_format((float) $assignment->latestPrice->price, 2) }}</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($assignment->latestPrice?->default_cost_price !== null)
                                    <span class="font-semibold">PHP {{ number_format((float) $assignment->latestPrice->default_cost_price, 2) }}</span>
                                @else
                                    <span class="text-gray-400">Not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $assignment->latestPrice?->price_date?->format('M d, Y') ?? 'Not set' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($assignment->latestPrice)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Priced
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        Needs Price
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @unless($brokerViewReadOnly)
                                        <a href="{{ route('broker.inventory.index', ['tab' => 'fishPrices', 'modal' => 'edit', 'edit' => $assignment->id]) }}"
                                           class="text-green-600 hover:text-green-900 transition-colors"
                                           title="{{ $assignment->latestPrice ? 'Edit price' : 'Set price' }}">
                                            <x-heroicon-o-pencil-square class="w-6 h-6" />
                                        </a>
                                        @if($assignment->latestPrice)
                                            <form action="{{ route('broker.fish-prices.destroy', $assignment->id) }}"
                                                  method="POST"
                                                  class="inline"
                                                  data-swal="delete"
                                                  data-record-name="{{ $assignment->fishType?->name ?? 'fish price' }}">
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
                                No fish name assignments found. Add fish names first before setting prices.
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
</div>
