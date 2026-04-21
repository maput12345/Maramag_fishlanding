<!-- Fish Boxes Tab Content -->
<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 space-y-4 sm:space-y-0">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Fish Boxes List</h2>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
            <form method="POST"
                  action="{{ route('broker.fish-boxes.return-to-stock') }}"
                  data-inventory-async="return-to-stock"
                  data-confirm-message="This will change all returned fish boxes back to In Stock. Continue?"
                  class="inline">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center space-x-2">
                    <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                    <span class="hidden sm:inline">Return to In Stock</span>
                    <span class="sm:hidden">Return to Stock</span>
                </button>
            </form>
            <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'create']) }}"
               class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center space-x-2">
                <x-heroicon-o-plus class="w-4 h-4" />
                <span class="hidden sm:inline">Add Fish Box</span>
                <span class="sm:hidden">Add Fish Box</span>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-6 summary-strip-wrap">
        <div class="summary-strip summary-strip--five">
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Boxes</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($fishBoxSummary['total'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">In Stock</p>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ number_format($fishBoxSummary['in_stock'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sold</p>
                <p class="mt-2 text-3xl font-bold text-blue-600">{{ number_format($fishBoxSummary['sold'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Returned</p>
                <p class="mt-2 text-3xl font-bold text-yellow-600">{{ number_format($fishBoxSummary['returned'] ?? 0) }}</p>
            </div>
            <div class="summary-strip-item">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Missing</p>
                <p class="mt-2 text-3xl font-bold text-red-600">{{ number_format($fishBoxSummary['missing'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Fish Box Modal -->
    @if(request('modal') === 'create' || request('modal') === 'edit')
        <x-app-modal
            :title="request('modal') === 'edit' ? 'Edit Fish Box' : 'Add Fish Box'"
            :subtitle="request('modal') === 'edit' ? 'Update the fish box details and current status.' : 'Register new reusable fish boxes with fish type and cost price.'"
            :close-url="route('broker.inventory.index', ['tab' => 'fishBoxes'])"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
                    <x-heroicon-o-archive-box class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <form action="{{ request('modal') === 'edit' ? route('broker.fish-boxes.update', request('edit', 0)) : route('broker.fish-boxes.store') }}" method="POST" class="space-y-6">
                @csrf
                @if(request('modal') === 'edit')
                    @method('PUT')
                @endif

                <div>
                    <label for="fish_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Fish Type <span class="text-red-500">*</span>
                    </label>
                    <select id="fish_type_id" name="fish_type_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Select Fish Type</option>
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
                        Cost Price <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-gray-500">PHP</span>
                        <input type="number"
                               id="cost_price"
                               name="cost_price"
                               min="0"
                               step="0.01"
                               required
                               value="{{ old('cost_price', request('modal') === 'edit' && $editingFishBox ? $editingFishBox->cost_price : '') }}"
                               class="w-full pl-14 pr-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Enter cost price">
                    </div>
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
                        {{ request('modal') === 'edit' ? 'Update Fish Box' : 'Create Fish Box' }}
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
                            placeholder="Search fish box name or fish type..."
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fish Type</label>
                    <select name="fish_type" x-model="fishType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Fish Types</option>
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
                        Apply
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
                        @if($fishBox->canBeEdited())
                            <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'edit', 'edit' => $fishBox->id]) }}"
                                class="text-gray-400 hover:text-green-600 transition-colors"
                                title="Edit Fish Box">
                                <x-heroicon-o-pencil-square class="w-6 h-6" />
                            </a>
                        @endif
                        @if($fishBox->canBeMarkedAsMissing())
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
                        @if($fishBox->canBeReturned())
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
                        @if($fishBox->canBeDeleted())
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
                    <p class="text-gray-500 mb-6">Get started by creating your first fish box.</p>
                    <a href="{{ route('broker.inventory.index', ['tab' => 'fishBoxes', 'modal' => 'create']) }}"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center space-x-2">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        <span>Create Fish Box</span>
                    </a>
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

</div>

