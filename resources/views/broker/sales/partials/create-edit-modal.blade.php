{{-- Create/Edit Sale Modal --}}
@php
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
@endphp
@if(request('modal') === 'create' || request('modal') === 'edit')
    @if($brokerViewReadOnly)
        <x-app-modal
            title="Support Actions Required"
            subtitle="Broker sales are read-only until an admin explicitly enables support actions."
            :close-url="$salesBaseUrl"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="space-y-6 py-2">
                <p class="text-sm text-gray-600">
                    This broker workspace is currently in read-only mode. Enable support actions first if you need to create or update sales for this broker.
                </p>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        data-sales-modal-close
                        data-close-url="{{ $salesBaseUrl }}"
                        class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Back
                    </button>
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
    @elseif(request('modal') === 'edit' && !$editingSales)
        {{-- Sales record not found --}}
        <x-app-modal
            title="Sale Not Found"
            subtitle="The sale you tried to edit is no longer available for this broker account."
            :close-url="$salesBaseUrl"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="py-4 text-center">
                <p class="mb-6 text-sm text-gray-500">The sale you're trying to edit could not be found or you don't have permission to access it.</p>
                <button
                    type="button"
                    data-sales-modal-close
                    data-close-url="{{ $salesBaseUrl }}"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                >
                    <x-heroicon-o-arrow-left class="mr-2 h-4 w-4" />
                    Back to Sales
                </button>
            </div>
        </x-app-modal>
    @else
        {{-- Unified Sale Modal --}}
        <x-app-modal
            :title="request('modal') === 'create' ? 'Transaction' : 'Edit Sale'"
            :subtitle="request('modal') === 'create' ? 'Assign boxes automatically, and keep buyer details in one place.' : 'Review and update this sale without losing the itemized details.'"
            :close-url="$salesBaseUrl"
            max-width="7xl"
            body-class="workspace-popup__body--soft"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-sm">
                    <x-heroicon-o-shopping-cart class="h-5 w-5" />
                </div>
            </x-slot:icon>

            @php
                $salesFormConfig = [
                    'fishBoxes' => $fishBoxes ?? [],
                    'fishTypes' => $fishTypes ?? [],
                    'fishPrices' => $fishPriceMap ?? [],
                    'mode' => request('modal') === 'edit' ? 'edit' : 'create',
                    'detailIndex' => count($salesDetails ?? []),
                ];
                $nextTransactionUrl = route('broker.sales.sales', array_merge($salesBaseQuery ?? [], ['modal' => 'create']));
            @endphp

            <script type="application/json" data-sales-form-config>{!! json_encode($salesFormConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

            <form action="{{ request('modal') === 'create' ? route('broker.sales.store') : route('broker.sales.update', $editingSales->id ?? '') }}"
                  method="POST"
                  data-sales-async-form
                  @if(request('modal') === 'create') data-sales-after-save-url="{{ $nextTransactionUrl }}" @endif
                  class="space-y-6">
                @csrf
                @if(request('modal') === 'edit')
                    @method('PUT')
                @endif

                {{-- Basic Information --}}
                <div>
                    <label for="sales_date" class="mb-2 block text-sm font-medium text-gray-700">
                        Sales Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="sales_date" name="sales_date" required
                           value="{{ request('modal') === 'edit' && $editingSales ? $editingSales->sales_date->format('Y-m-d') : (old('sales_date', date('Y-m-d'))) }}"
                           class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    <input type="hidden" id="total_amount" name="total_amount"
                           value="{{ request('modal') === 'edit' && $editingSales ? $editingSales->total_amount : (old('total_amount', '')) }}">
                    @error('sales_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sales Details --}}
                <div class="space-y-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Sales Details <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500">Price per box auto-fills from your current broker fish price list when available.</p>

                    <div class="space-y-4" id="sales-details-container">
                        @foreach($salesDetails as $index => $detail)
                            <div class="sales-detail-row rounded-2xl border border-gray-200 bg-white/80 p-6" data-index="{{ $index }}">
                                <div class="flex flex-wrap gap-4">
                                    <div class="min-w-[200px] flex-1">
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Fish</label>
                                        <select name="sales_details[{{ $index }}][fish_type_id]"
                                                class="fish-type-select h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                required>
                                            <option value="">Select Fish</option>
                                            @foreach($fishTypes as $fishType)
                                                @php
                                                    $suggestedPrice = $fishPriceMap[(string) $fishType->id] ?? $fishPriceMap[$fishType->id] ?? null;
                                                @endphp
                                                <option value="{{ $fishType->id }}"
                                                        data-suggested-price="{{ $suggestedPrice !== null ? $suggestedPrice : '' }}"
                                                        {{ (string)($detail['fish_type_id'] ?? '') === (string)$fishType->id ? 'selected' : '' }}>
                                                    {{ $fishType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="min-w-[200px] flex-1">
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Fish Box</label>
                                        <div class="fish-boxes-container max-h-32 space-y-2 overflow-y-auto">
                                            @if(is_array($detail['box_id']) && count($detail['box_id']) > 0)
                                                @foreach($detail['box_id'] as $boxIndex => $boxId)
                                                    <div class="fish-box-item">
                                                        <select name="sales_details[{{ $index }}][box_id][]"
                                                                class="fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500"
                                                                disabled>
                                                            <option value="{{ $boxId }}" selected>{{ $detail['box_labels'][$boxIndex] ?? ('Fish Box #' . $boxId) }}</option>
                                                        </select>
                                                        <input type="hidden" name="sales_details[{{ $index }}][box_id][]" class="fish-box-hidden-input" value="{{ $boxId }}">
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="fish-box-item">
                                                    <select name="sales_details[{{ $index }}][box_id][]"
                                                            class="fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500"
                                                            disabled>
                                                            <option value="">Auto-assign available box</option>
                                                    </select>
                                                    <input type="hidden" name="sales_details[{{ $index }}][box_id][]" class="fish-box-hidden-input">
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="min-w-[150px] flex-1">
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Price per Box</label>
                                        <input type="number" name="sales_details[{{ $index }}][unit_price]"
                                               value="{{ $detail['unit_price'] ?? '' }}"
                                               step="0.01" min="0"
                                               class="unit-price-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="0.00">
                                    </div>

                                    <div class="min-w-[120px] flex-1">
                                        <label class="mb-2 block text-sm font-medium text-gray-700">QTY</label>
                                        <input type="number" name="sales_details[{{ $index }}][quantity]"
                                               value="{{ $detail['quantity'] ?? '1' }}"
                                               min="1"
                                               class="quantity-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="1">
                                    </div>

                                    <div class="min-w-[150px] flex-1">
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Sub Total</label>
                                        <input type="number" name="sales_details[{{ $index }}][sub_total]"
                                               value="{{ $detail['sub_total'] ?? '' }}"
                                               step="0.01" min="0"
                                               class="sub-total-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-500"
                                               readonly>
                                    </div>

                                    <div class="flex items-end">
                                        <button type="button"
                                                class="remove-detail-btn rounded-lg p-2 text-red-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                                aria-label="Remove sales detail">
                                            <x-heroicon-o-trash class="h-6 w-6" />
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" name="sales_details[{{ $index }}][item]" class="item-input" value="{{ $detail['item'] ?? '' }}">
                                <input type="hidden" name="sales_details[{{ $index }}][item_description]" class="item-description-input" value="{{ $detail['item_description'] ?? '' }}">
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-xl bg-blue-50 p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold text-gray-900">TOTAL:</span>
                            <span class="text-2xl font-bold text-gray-900" id="total-amount-display">PHP 0.00</span>
                        </div>
                    </div>
                    @error('total_amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="button" id="add-sales-detail-btn"
                                class="inline-flex h-12 w-full flex-shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-blue-700 sm:w-auto"
                                style="min-width: 13rem;">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            <span>Add Sales Detail</span>
                        </button>
                        <button type="button" id="scan-qr-btn"
                                class="inline-flex h-12 w-full flex-shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-blue-700 sm:w-auto"
                                style="min-width: 13rem;">
                            <x-heroicon-o-qr-code class="h-4 w-4" />
                            <span>Scan QR Code</span>
                        </button>
                    </div>

                    @error('sales_details')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Buyer Information --}}
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="buyer_name" class="mb-2 block text-sm font-medium text-gray-700">
                            Buyer Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="buyer_name" name="buyer_name"
                               value="{{ request('modal') === 'edit' && $editingSales ? $editingSales->buyer_name : (old('buyer_name', '')) }}"
                               class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter buyer name">
                        @error('buyer_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="buyer_contact" class="mb-2 block text-sm font-medium text-gray-700">
                            Buyer Contact
                        </label>
                        <input type="text" id="buyer_contact" name="buyer_contact"
                               value="{{ request('modal') === 'edit' && $editingSales ? $editingSales->buyer_contact : (old('buyer_contact', '')) }}"
                               class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter buyer contact">
                        @error('buyer_contact')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if(request('modal') === 'create')
                    <div class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6">

                        <div>
                            <label for="initial_paid_amount" class="mb-2 block text-sm font-medium text-gray-700">
                                Paid Amount
                            </label>
                            <input type="number" id="initial_paid_amount" name="initial_paid_amount"
                                   value="{{ old('initial_paid_amount', '') }}"
                                   step="0.01" min="0.01"
                                   data-currency-input="true"
                                   class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                   placeholder="0.00">
                            <div class="mt-2 text-xs text-gray-500">
                                Maximum payment: PHP <span id="initial-payment-max-amount">0.00</span>
                            </div>
                            <div id="initial-payment-error" class="mt-2 hidden text-sm text-red-600"></div>
                            @error('initial_paid_amount')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="initial_payment_date" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Date
                            </label>
                            <input type="date" id="initial_payment_date" name="initial_payment_date"
                                   value="{{ old('initial_payment_date', '') }}"
                                   class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('initial_payment_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="initial_payment_method" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Method
                            </label>
                            <select id="initial_payment_method" name="initial_payment_method"
                                    class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Payment Method</option>
                                <option value="Cash" {{ old('initial_payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="GCash" {{ old('initial_payment_method') == 'GCash' ? 'selected' : '' }}>GCash</option>
                                <option value="Bank Transfer" {{ old('initial_payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Check" {{ old('initial_payment_method') == 'Check' ? 'selected' : '' }}>Check</option>
                                <option value="Other" {{ old('initial_payment_method') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('initial_payment_method')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                {{-- Modal Footer --}}
                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <button
                        type="button"
                        data-sales-modal-close
                        data-close-url="{{ $salesBaseUrl }}"
                        class="inline-flex h-12 items-center justify-center rounded-xl bg-gray-100 px-6 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200"
                        style="min-width: 9.5rem;">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex h-12 items-center justify-center rounded-xl px-6 text-sm font-semibold text-white shadow-sm transition-colors {{ request('modal') === 'create' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }}"
                            style="min-width: 9.5rem;">
                        {{ request('modal') === 'create' ? 'Transaction' : 'Update Sale' }}
                    </button>
                </div>
            </form>
        </x-app-modal>
    @endif
@endif
