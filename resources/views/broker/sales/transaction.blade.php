@php
$brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
    $breadcrumbs = [
        ['title' => 'Cashier Transaction'],
    ];
    $isCashierStaff = auth()->check() && auth()->user()->isCashier();
    $isPosMode = $isCashierStaff || request()->boolean('pos');
    $transactionUrl = route('broker.transaction', $isPosMode ? ['pos' => 1] : []);
    $posLaunchUrl = route('broker.transaction', ['pos' => 1]);
    $salesBaseUrl = $transactionUrl;
    $salesFormConfig = [
        'fishBoxes' => $fishBoxes ?? [],
        'fishTypes' => $fishTypes ?? [],
        'fishPrices' => $fishPriceMap ?? [],
        'mode' => 'create',
        'detailIndex' => count($salesDetails ?? []),
    ];
    $buyerSectionInitiallyVisible = old('buyer_first_name') !== null
        || old('buyer_middle_name') !== null
        || old('buyer_last_name') !== null
        || old('buyer_contact') !== null
        || old('initial_paid_amount') !== null
        || old('initial_payment_method') !== null
        || $errors->has('buyer_first_name')
        || $errors->has('buyer_last_name')
        || $errors->has('buyer_contact')
        || $errors->has('initial_paid_amount')
        || $errors->has('initial_payment_method');
    $printReceiptScriptUrl = asset('js/print-receipt.js') . '?v=' . filemtime(public_path('js/print-receipt.js'));
    $qrScannerLegacyScriptUrl = asset('js/qr-scanner-legacy.min.js') . '?v=' . filemtime(public_path('js/qr-scanner-legacy.min.js'));
    $salesQrScannerScriptUrl = asset('js/sales-qr-scanner.js') . '?v=' . filemtime(public_path('js/sales-qr-scanner.js'));
    $salesFormScriptUrl = asset('js/sales-form.js') . '?v=' . filemtime(public_path('js/sales-form.js'));
    $salesPageScriptUrl = asset('js/sales-page.js') . '?v=' . filemtime(public_path('js/sales-page.js'));
    $remoteSalesScannerScriptUrl = asset('js/remote-sales-scanner.js') . '?v=' . filemtime(public_path('js/remote-sales-scanner.js'));
@endphp
@extends('layouts.broker')

@section('content')
    <style>
        .transaction-buyer-modal {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(248, 250, 252, 0.72);
            backdrop-filter: blur(2px);
            padding: 1rem;
        }

        .transaction-buyer-modal.is-hidden {
            display: none;
        }

        .transaction-buyer-panel {
            width: min(46rem, 100%);
            max-height: min(42rem, calc(100vh - 2rem));
            overflow-y: auto;
            border: 1px solid #d8e1ef;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        }

        .transaction-buyer-body {
            padding: 1.25rem;
        }

        .transaction-buyer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.875rem;
        }

        @media (min-width: 768px) {
            .transaction-buyer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .transaction-buyer-field {
            height: 2.875rem !important;
            border-radius: 0.875rem !important;
            padding: 0 1rem !important;
        }

        .transaction-buyer-actions {
            position: sticky;
            bottom: 0;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid #eef2f7;
            background: #fff;
            padding: 1rem 1.25rem;
        }

        @media (max-width: 640px) {
            .transaction-buyer-actions {
                flex-direction: column-reverse;
            }
        }
    </style>

    <div
        id="sales-page-root"
        class="relative w-full content-spacing"
        data-sales-page
        data-sales-base-url="{{ $transactionUrl }}"
        @if(request()->boolean('auto_print') && request()->filled('print')) data-sales-updated-token="{{ request('print') }}" @endif
    >
        <div id="sales-page-fragment" data-sales-form-root>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <span class="dashboard-kicker">Cashier Terminal</span>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:justify-end">
                    @if($isPosMode)
                        <a href="{{ route('broker.sales.sales') }}" class="app-button app-button--secondary h-12 w-full px-4 text-sm sm:w-auto">
                            <x-heroicon-o-banknotes class="h-4 w-4" />
                            <span>{{ $isCashierStaff ? 'My Transactions' : 'Sales Records' }}</span>
                        </a>
                    @endif
                    @unless($isPosMode)
                        <a href="{{ $posLaunchUrl }}"
                           target="_blank"
                           rel="noopener"
                           class="app-button app-button--dark h-14 w-14 px-0 shadow-md ring-1 ring-white/20"
                           aria-label="Open cashier-only terminal"
                           title="Open cashier-only terminal">
                            <x-heroicon-o-identification class="h-7 w-7" />
                        </a>
                    @endunless
                </div>
            </div>

            @if($brokerViewReadOnly)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Broker sales are read-only until an admin explicitly enables support actions.
                </div>
            @else
                <section class="rounded-xl bg-white p-4 shadow-lg">
                    <script type="application/json" data-sales-form-config>{!! json_encode($salesFormConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
                    <p class="sr-only">Price per box auto-fills from your current broker fish price list when available.</p>

                    <form action="{{ route('broker.sales.store') }}"
                          method="POST"
                          data-sales-async-form
                          data-sales-sync-submit
                          data-transaction-step-form
                          data-buyer-section-visible="{{ $buyerSectionInitiallyVisible ? 'true' : 'false' }}"
                          data-sales-after-save-url="{{ $transactionUrl }}"
                          class="space-y-4"
                          novalidate>
                        @csrf
                        <input type="hidden" name="after_save" value="transaction">
                        @if($isPosMode)
                            <input type="hidden" name="pos_mode" value="1">
                        @endif
                        <input type="hidden" id="sales_date" name="sales_date" value="{{ old('sales_date', date('Y-m-d')) }}">
                        <input type="hidden" id="total_amount" name="total_amount" value="{{ old('total_amount', '') }}">

                        <div class="sales-details-card space-y-3">
                            <div class="sales-details-header flex flex-col gap-3 bg-white sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Transaction <span class="text-red-500">*</span>
                                    </label>
                                </div>
                                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:justify-end">
                                    <button type="button" id="add-sales-detail-btn"
                                            class="app-button app-button--dark h-12 w-full px-5 text-sm sm:w-auto">
                                        <x-heroicon-o-plus class="h-4 w-4" />
                                        <span>Add Fish</span>
                                    </button>
                                    <button type="button" id="scan-qr-btn"
                                            class="app-button app-button--dark h-12 w-full px-5 text-sm sm:w-auto">
                                        <x-heroicon-o-qr-code class="h-4 w-4" />
                                        <span>Scan QR Code</span>
                                    </button>
                                    <button type="button" data-remote-sales-scanner-open
                                            class="app-button app-button--secondary h-12 w-full px-5 text-sm sm:w-auto">
                                        <x-heroicon-o-device-phone-mobile class="h-4 w-4" />
                                        <span>Phone Scanner</span>
                                    </button>
                                </div>
                            </div>

                            <div class="relative rounded-2xl border border-blue-100 bg-blue-50/60 p-3" data-fish-quick-add>
                                <label for="fish-quick-add-search" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Search Fish to Add
                                </label>
                                <div class="flex flex-col gap-2 lg:flex-row">
                                    <input id="fish-quick-add-search"
                                           type="search"
                                           class="h-12 min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                           placeholder="Type fish name, then select to add..."
                                           autocomplete="off"
                                           data-fish-quick-add-input>
                                    <button type="button"
                                            class="app-button app-button--secondary h-12 px-5 text-sm"
                                            data-fish-quick-add-clear>
                                        Clear
                                    </button>
                                </div>
                                <div class="absolute left-3 right-3 top-full z-20 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                     data-fish-quick-add-results></div>
                            </div>

                            <div class="sales-details-scroll-area space-y-3 pr-2"
                                 id="sales-details-container"
                                 style="max-height: min(32rem, calc(100vh - 22rem)); overflow-y: auto; overscroll-behavior: contain;">
                                @foreach($salesDetails as $index => $detail)
                                    <div class="sales-detail-row rounded-2xl border border-gray-200 bg-white/80 p-6" data-index="{{ $index }}">
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-[minmax(10rem,1.45fr)_minmax(10rem,1.45fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_5.5rem_minmax(8rem,1fr)_2.75rem] xl:items-start">
                                            <div class="min-w-0">
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

                                            <div class="min-w-0">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">Fish Box</label>
                                                <div class="fish-boxes-container max-h-32 space-y-2 overflow-y-auto">
                                                    <div class="fish-box-item">
                                                        <select name="sales_details[{{ $index }}][box_id][]"
                                                                class="fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500"
                                                                disabled>
                                                            <option value="">Auto-assign available box</option>
                                                        </select>
                                                        <input type="hidden" name="sales_details[{{ $index }}][box_id][]" class="fish-box-hidden-input">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="min-w-0">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">Price per Box</label>
                                               <input type="text" name="sales_details[{{ $index }}][unit_price]"
                                                      value="{{ isset($detail['unit_price']) && $detail['unit_price'] !== '' ? number_format((float) $detail['unit_price'], 2) : '' }}"
                                                      data-currency-input="true"
                                                      inputmode="decimal"
                                                       class="unit-price-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-right text-sm tabular-nums text-gray-500"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>

                                            <div class="min-w-0">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">Discount Type</label>
                                                <select name="sales_details[{{ $index }}][discount_mode]"
                                                        class="discount-mode-select h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                                    <option value="percent" {{ ($detail['discount_mode'] ?? 'percent') === 'percent' ? 'selected' : '' }}>Percent</option>
                                                    <option value="amount" {{ ($detail['discount_mode'] ?? '') === 'amount' ? 'selected' : '' }}>Amount</option>
                                                </select>
                                            </div>

                                            <div class="min-w-0">
                                                @php
                                                    $discountMode = $detail['discount_mode'] ?? 'percent';
                                                    $discountPercent = isset($detail['discount_percent']) && $detail['discount_percent'] !== ''
                                                        ? (float) $detail['discount_percent']
                                                        : ((isset($detail['discount'], $detail['unit_price']) && (float) $detail['unit_price'] > 0) ? (((float) $detail['discount'] / (float) $detail['unit_price']) * 100) : null);
                                                    $discountAmount = isset($detail['discount']) && $detail['discount'] !== '' ? (float) $detail['discount'] : null;
                                                    $discountValue = $discountMode === 'amount' ? $discountAmount : $discountPercent;
                                                @endphp
                                                <label class="discount-value-label mb-2 block text-sm font-medium text-gray-700">{{ $discountMode === 'amount' ? 'Discount Amount' : 'Discount %' }}</label>
                                                <input type="text" name="sales_details[{{ $index }}][discount_value]"
                                                       value="{{ $discountValue !== null ? number_format($discountValue, 2) : '' }}"
                                                       inputmode="decimal"
                                                       class="discount-value-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm tabular-nums text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                       placeholder="0.00">
                                                <input type="hidden" name="sales_details[{{ $index }}][discount_percent]" class="discount-percent-input" value="{{ $discountPercent !== null ? number_format($discountPercent, 2, '.', '') : '' }}">
                                                <input type="hidden" name="sales_details[{{ $index }}][discount]" class="discount-input" value="{{ $discountAmount !== null ? number_format($discountAmount, 2, '.', '') : '' }}">
                                            </div>

                                            <div class="min-w-0">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">QTY</label>
                                                <input type="number" name="sales_details[{{ $index }}][quantity]"
                                                       value="{{ $detail['quantity'] ?? '1' }}"
                                                       min="1"
                                                       class="quantity-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                       placeholder="1">
                                            </div>

                                            <div class="min-w-0">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">Sub Total</label>
                                               <input type="text" name="sales_details[{{ $index }}][sub_total]"
                                                      value="{{ isset($detail['sub_total']) && $detail['sub_total'] !== '' ? number_format((float) $detail['sub_total'], 2) : '' }}"
                                                      inputmode="decimal"
                                                       class="sub-total-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm tabular-nums text-gray-500"
                                                       readonly>
                                            </div>

                                            <div class="flex items-end justify-center xl:pt-7">
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

                            <div class="rounded-xl bg-blue-50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center justify-between gap-4 sm:flex-1">
                                        <span class="text-lg font-semibold text-gray-900">TOTAL:</span>
                                        <span class="text-right text-2xl font-bold tabular-nums text-gray-900" id="total-amount-display">₱0.00</span>
                                    </div>
                                    <button type="submit"
                                            data-transaction-step-submit
                                            class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-green-600 px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-700 sm:w-auto"
                                            style="min-width: 11rem;">
                                        {{ $buyerSectionInitiallyVisible ? 'Confirm Transaction' : 'Save Transaction' }}
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div class="{{ $buyerSectionInitiallyVisible ? '' : 'is-hidden' }} transaction-buyer-modal"
                             data-buyer-info-section
                             role="dialog"
                             aria-modal="true"
                             aria-labelledby="buyer-info-modal-title">
                            <div class="transaction-buyer-panel">
                                <div class="transaction-buyer-body">
                                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 id="buyer-info-modal-title" class="text-lg font-semibold text-gray-900">Buyer Information</h2>
                                        <p class="mt-1 text-xs text-gray-500">Complete these details to confirm the full transaction.</p>
                                    </div>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        <div class="rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold text-gray-900">
                                            Total: <span id="buyer-total-amount-display" class="tabular-nums">₱0.00</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="relative" data-regular-buyer-picker>
                                        <label for="regular_buyer_search" class="mb-2 block text-sm font-medium text-gray-700">Regular Customer</label>
                                        <script type="application/json" data-regular-buyers-json>{!! json_encode($regularBuyers ?? collect(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                                        <input type="hidden"
                                               name="buyer_id"
                                               value="{{ old('buyer_id', '') }}"
                                               data-regular-buyer-id
                                               data-buyer-final-field
                                               {{ $buyerSectionInitiallyVisible ? '' : 'disabled' }}>
                                        <input type="search"
                                               id="regular_buyer_search"
                                               data-regular-buyer-search
                                               data-buyer-final-field
                                               autocomplete="off"
                                               class="transaction-buyer-field w-full border border-gray-200 bg-white text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="Search name or contact..."
                                               {{ $buyerSectionInitiallyVisible ? '' : 'disabled' }}>
                                        <div class="absolute left-0 right-0 top-full z-30 mt-2 hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                             data-regular-buyer-results></div>
                                    </div>
                                </div>

                                <div class="transaction-buyer-grid">
                                    <div>
                                        <label for="buyer_first_name" class="mb-2 block text-sm font-medium text-gray-700">
                                            First Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="buyer_first_name" name="buyer_first_name"
                                               value="{{ old('buyer_first_name', '') }}"
                                               data-buyer-final-field
                                               class="transaction-buyer-field w-full border border-gray-200 bg-white text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="Enter first name"
                                               {{ $buyerSectionInitiallyVisible ? 'required' : 'disabled' }}>
                                    </div>

                                    <div>
                                        <label for="buyer_middle_name" class="mb-2 block text-sm font-medium text-gray-700">Middle Name</label>
                                        <input type="text" id="buyer_middle_name" name="buyer_middle_name"
                                               value="{{ old('buyer_middle_name', '') }}"
                                               data-buyer-final-field
                                               class="transaction-buyer-field w-full border border-gray-200 bg-white text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="Optional"
                                               {{ $buyerSectionInitiallyVisible ? '' : 'disabled' }}>
                                    </div>

                                    <div>
                                        <label for="buyer_last_name" class="mb-2 block text-sm font-medium text-gray-700">
                                            Last Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="buyer_last_name" name="buyer_last_name"
                                               value="{{ old('buyer_last_name', '') }}"
                                               data-buyer-final-field
                                               class="transaction-buyer-field w-full border border-gray-200 bg-white text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="Enter last name"
                                               {{ $buyerSectionInitiallyVisible ? 'required' : 'disabled' }}>
                                    </div>

                                    <div>
                                        <label for="buyer_contact" class="mb-2 block text-sm font-medium text-gray-700">
                                            Contact Number <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="buyer_contact" name="buyer_contact"
                                               value="{{ old('buyer_contact', '') }}"
                                               inputmode="numeric"
                                               maxlength="11"
                                               pattern="09[0-9]{9}"
                                               data-buyer-final-field
                                               class="transaction-buyer-field w-full border border-gray-200 bg-white text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                               placeholder="09XXXXXXXXX"
                                               {{ $buyerSectionInitiallyVisible ? 'required' : 'disabled' }}>
                                    </div>
                                </div>



                                <input type="hidden" id="initial_payment_date" name="initial_payment_date" value="{{ old('initial_payment_date', date('Y-m-d')) }}">

                                <div class="transaction-buyer-grid mt-4">
                                    <div>
                                        <label for="initial_paid_amount" class="mb-2 block text-sm font-medium text-gray-700">
                                            Paid Amount
                                        </label>
                                       <div class="currency-input-group">
                                           <span class="currency-input-symbol">₱</span>
                                           <input type="text" id="initial_paid_amount" name="initial_paid_amount"
                                                  value="{{ old('initial_paid_amount') !== null ? number_format((float) old('initial_paid_amount'), 2) : '' }}"
                                                  inputmode="decimal"
                                                  data-currency-input="true"
                                                  data-buyer-final-field
                                                  class="currency-input-field transaction-buyer-field"
                                                  placeholder="0.00"
                                                  {{ $buyerSectionInitiallyVisible ? '' : 'disabled' }}>
                                       </div>
                                        <div class="mt-2 text-xs text-gray-500">
                                            Maximum payment: ₱<span id="initial-payment-max-amount" class="inline-block min-w-[4rem] text-right tabular-nums">0.00</span>
                                        </div>
                                        <div id="initial-payment-error" class="mt-2 hidden text-sm text-red-600"></div>
                                    </div>

                                    <div>
                                        <label for="initial_payment_method" class="mb-2 block text-sm font-medium text-gray-700">
                                            Payment Method
                                        </label>
                                        <select id="initial_payment_method" name="initial_payment_method"
                                                data-buyer-final-field
                                                class="transaction-buyer-field w-full border border-gray-200 bg-white text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                {{ $buyerSectionInitiallyVisible ? '' : 'disabled' }}>
                                            <option value="">Select Payment Method</option>
                                            <option value="Cash" {{ old('initial_payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="GCash" {{ old('initial_payment_method') == 'GCash' ? 'selected' : '' }}>GCash</option>
                                            <option value="Bank Transfer" {{ old('initial_payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="Check" {{ old('initial_payment_method') == 'Check' ? 'selected' : '' }}>Check</option>
                                            <option value="Other" {{ old('initial_payment_method') == 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>

                                </div>
                                <div class="transaction-buyer-actions">
                                    <button type="button"
                                            data-buyer-info-cancel
                                            class="inline-flex h-12 items-center justify-center rounded-xl border border-gray-200 bg-white px-6 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            data-transaction-step-submit
                                            class="inline-flex h-12 items-center justify-center rounded-xl bg-green-600 px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-700"
                                            style="min-width: 11rem;">
                                        Confirm Transaction
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="hidden"
                             data-transaction-action-bar>
                            <div class="flex w-[calc(100vw-2rem)] max-w-md flex-col gap-2 rounded-2xl border border-gray-200 bg-white/95 p-3 shadow-xl backdrop-blur sm:w-auto sm:max-w-none sm:flex-row sm:items-center">
                                <p class="hidden text-sm text-gray-500 sm:block" data-transaction-step-hint>
                                    {{ $buyerSectionInitiallyVisible ? 'Review buyer details, then confirm.' : 'Save items first, then enter buyer information.' }}
                                </p>
                            </div>
                        </div>
                    </form>
                </section>
            @endif
            @include('broker.sales.partials.print-modal')
            @if(request('auto_print') && $printingSales)
                <div data-auto-print-receipt="true" data-receipt-id="{{ $printingSales->id }}" hidden></div>
            @endif
        </div>
    </div>

    <template id="sales-detail-row-template">
        <div class="sales-detail-row rounded-2xl border border-gray-200 bg-white/80 p-6">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-[minmax(10rem,1.45fr)_minmax(10rem,1.45fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_5.5rem_minmax(8rem,1fr)_2.75rem] xl:items-start">
                <div class="min-w-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Fish</label>
                    <select name="sales_details[INDEX][fish_type_id]"
                            class="fish-type-select h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Select Fish</option>
                        @foreach($fishTypes ?? [] as $fishType)
                            @php
$suggestedPrice = $fishPriceMap[(string) $fishType->id] ?? $fishPriceMap[$fishType->id] ?? null;
                            @endphp
<option value="{{ $fishType->id }}" data-suggested-price="{{ $suggestedPrice !== null ? $suggestedPrice : '' }}">
                                {{ $fishType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Fish Box</label>
                    <div class="fish-boxes-container max-h-32 space-y-2 overflow-y-auto">
                        <div class="fish-box-item">
                            <select class="fish-box-select h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500" disabled>
                                <option value="">Auto-assign available box</option>
                            </select>
                            <input type="hidden" name="sales_details[INDEX][box_id][]" class="fish-box-hidden-input">
                        </div>
                    </div>
                </div>
                <div class="min-w-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Price per Box</label>
                    <input type="text" name="sales_details[INDEX][unit_price]" inputmode="decimal"
                           data-currency-input="true"
                           class="unit-price-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-right text-sm tabular-nums text-gray-500"
                           placeholder="0.00"
                           readonly>
                </div>
                <div class="min-w-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Discount Type</label>
                    <select name="sales_details[INDEX][discount_mode]"
                            class="discount-mode-select h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        <option value="percent" selected>Percent</option>
                        <option value="amount">Amount</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="discount-value-label mb-2 block text-sm font-medium text-gray-700">Discount %</label>
                    <input type="text" name="sales_details[INDEX][discount_value]" inputmode="decimal"
                           class="discount-value-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm tabular-nums text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                    <input type="hidden" name="sales_details[INDEX][discount_percent]" class="discount-percent-input">
                    <input type="hidden" name="sales_details[INDEX][discount]" class="discount-input">
                </div>
                <div class="min-w-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">QTY</label>
                    <input type="number" name="sales_details[INDEX][quantity]" value="1" min="1"
                           class="quantity-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="1">
                </div>
                <div class="min-w-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Sub Total</label>
                    <input type="text" name="sales_details[INDEX][sub_total]" inputmode="decimal"
                           class="sub-total-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm tabular-nums text-gray-500"
                           readonly>
                </div>
                <div class="flex items-end justify-center xl:pt-7">
                    <button type="button" class="remove-detail-btn rounded-lg p-2 text-red-500 transition-colors hover:bg-red-50 hover:text-red-600" aria-label="Remove sales detail">
                        <x-heroicon-o-trash class="h-6 w-6" />
                    </button>
                </div>
            </div>
            <input type="hidden" name="sales_details[INDEX][item]" class="item-input" value="">
            <input type="hidden" name="sales_details[INDEX][item_description]" class="item-description-input" value="">
        </div>
    </template>

    <script>
        window.salesQrScannerConfig = {
            lookupUrlTemplate: @json(route('broker.fish-boxes.qr', ['qrCode' => '__QR_CODE__']))
        };
        window.remoteSalesScannerConfig = {
            createUrl: @json(route('broker.sales.scan-sessions.store', [], false)),
            closeUrlTemplate: @json(route('broker.sales.scan-sessions.close', ['token' => '__TOKEN__'], false))
        };
        window.brokerTransactionAssets = {
            qrScannerLegacy: @json($qrScannerLegacyScriptUrl),
            salesQrScanner: @json($salesQrScannerScriptUrl),
            remoteSalesScanner: @json($remoteSalesScannerScriptUrl)
        };
    </script>
    <script src="{{ $printReceiptScriptUrl }}" defer></script>
    <script src="{{ $salesFormScriptUrl }}" defer></script>
    <script src="{{ $salesPageScriptUrl }}" defer></script>
    <script src="{{ $salesQrScannerScriptUrl }}" defer></script>
    <script src="{{ $remoteSalesScannerScriptUrl }}" defer></script>
    <script>
        const brokerTransactionScriptPromises = {};

        function loadBrokerTransactionScript(key, url) {
            if (!url) {
                return Promise.reject(new Error(`${key} asset is not configured.`));
            }

            if (brokerTransactionScriptPromises[key]) {
                return brokerTransactionScriptPromises[key];
            }

            brokerTransactionScriptPromises[key] = new Promise((resolve, reject) => {
                const existing = document.querySelector(`script[data-broker-transaction-asset="${key}"]`);

                if (existing) {
                    existing.addEventListener('load', resolve, { once: true });
                    existing.addEventListener('error', reject, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = url;
                script.defer = true;
                script.dataset.brokerTransactionAsset = key;
                script.onload = resolve;
                script.onerror = () => reject(new Error(`Unable to load ${key}.`));
                document.body.appendChild(script);
            });

            return brokerTransactionScriptPromises[key];
        }

        async function ensureSalesQrScannerReady() {
            const assets = window.brokerTransactionAssets || {};

            if (typeof window.QrScanner !== 'function') {
                await loadBrokerTransactionScript('qrScannerLegacy', assets.qrScannerLegacy);
            }

            if (typeof window.SalesQRScanner !== 'function') {
                await loadBrokerTransactionScript('salesQrScanner', assets.salesQrScanner);
            }

            if (!window.salesQrScanner && typeof window.SalesQRScanner === 'function') {
                window.salesQrScanner = new window.SalesQRScanner();
            }

            return window.salesQrScanner;
        }

        async function openSalesQrScanner(button) {
            const originalText = button?.querySelector('span')?.textContent || '';

            try {
                if (button) {
                    button.disabled = true;
                    button.querySelector('span').textContent = 'Loading...';
                }

                const scanner = await ensureSalesQrScannerReady();

                if (scanner) {
                    scanner.openModal();
                    return;
                }

                throw new Error('QR Scanner not initialized.');
            } catch (error) {
                if (window.toastr) {
                    toastr.error('QR Scanner failed to load. Please refresh the page.');
                }
            } finally {
                if (button) {
                    button.disabled = false;
                    button.querySelector('span').textContent = originalText || 'Scan QR Code';
                }
            }
        }

        async function openRemoteSalesScanner(button) {
            const assets = window.brokerTransactionAssets || {};
            const originalText = button?.querySelector('span')?.textContent || '';

            try {
                if (button) {
                    button.disabled = true;
                    button.querySelector('span').textContent = 'Loading...';
                }

                await ensureSalesQrScannerReady();

                if (typeof window.openRemoteSalesScannerSession !== 'function') {
                    await loadBrokerTransactionScript('remoteSalesScanner', assets.remoteSalesScanner);
                }

                if (typeof window.bindRemoteSalesScannerButtons === 'function') {
                    window.bindRemoteSalesScannerButtons();
                }

                if (typeof window.openRemoteSalesScannerSession === 'function') {
                    await window.openRemoteSalesScannerSession();
                    return;
                }

                throw new Error('Phone scanner not initialized.');
            } catch (error) {
                if (window.toastr) {
                    toastr.error('Phone Scanner failed to load. Please refresh the page.');
                }
            } finally {
                if (button) {
                    button.disabled = false;
                    button.querySelector('span').textContent = originalText || 'Phone Scanner';
                }
            }
        }

        function getActiveSalesFormRoot() {
            return document.querySelector('[data-sales-form-root]') || document;
        }

        function getCurrentSalesFormConfig(root = getActiveSalesFormRoot()) {
            const configNode = root?.querySelector('[data-sales-form-config]');

            if (!configNode) {
                return null;
            }

            try {
                return JSON.parse(configNode.textContent);
            } catch (error) {
                return null;
            }
        }

        function initializeTransactionBuyerStep(root = getActiveSalesFormRoot()) {
            const form = root.querySelector('[data-transaction-step-form]');

            if (!form || form.dataset.transactionStepBound === 'true') {
                return;
            }

            form.dataset.transactionStepBound = 'true';

            const buyerSection = form.querySelector('[data-buyer-info-section]');
            const buyerFields = Array.from(form.querySelectorAll('[data-buyer-final-field]'));
            const actionButtons = Array.from(form.querySelectorAll('[data-transaction-step-submit]'));
            const cancelButtons = Array.from(form.querySelectorAll('[data-buyer-info-cancel]'));
            const stepHint = form.querySelector('[data-transaction-step-hint]');
            const buyerTotalDisplay = form.querySelector('#buyer-total-amount-display');
            const totalAmountDisplay = form.querySelector('#total-amount-display');
            const requiredBuyerFieldIds = new Set([
                'buyer_first_name',
                'buyer_last_name',
                'buyer_contact',
            ]);

            const showMessage = (message) => {
                let summary = form.querySelector('[data-transaction-step-error]');

                if (!summary) {
                    summary = document.createElement('div');
                    summary.setAttribute('data-transaction-step-error', 'true');
                    summary.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
                    form.prepend(summary);
                }

                summary.textContent = message;

                if (window.toastr) {
                    toastr.error(message);
                }
            };

            const clearMessage = () => {
                form.querySelector('[data-transaction-step-error]')?.remove();
            };

            const hasSalesItem = () => {
                return Array.from(form.querySelectorAll('.sales-detail-row')).some((row) => {
                    const fishTypeSelect = row.querySelector('.fish-type-select');
                    return fishTypeSelect && fishTypeSelect.value;
                });
            };

            const syncBuyerTotal = () => {
                if (buyerTotalDisplay && totalAmountDisplay) {
                    buyerTotalDisplay.textContent = totalAmountDisplay.textContent || '₱0.00';
                }
            };

            const setBuyerStepVisible = (visible, options = {}) => {
                form.dataset.buyerSectionVisible = visible ? 'true' : 'false';

                if (buyerSection) {
                    buyerSection.classList.toggle('is-hidden', !visible);
                }

                buyerFields.forEach((field) => {
                    field.disabled = !visible;
                    field.required = visible && requiredBuyerFieldIds.has(field.id);
                });

                actionButtons.forEach((button) => {
                    button.textContent = visible ? 'Confirm Transaction' : 'Save Transaction';
                });

                if (stepHint) {
                    stepHint.textContent = visible
                        ? 'Review buyer details, then confirm.'
                        : 'Save items first, then enter buyer information.';
                }

                syncBuyerTotal();

                if (visible && options.focus !== false) {
                    window.requestAnimationFrame(() => {
                        form.querySelector('#buyer_first_name')?.focus({ preventScroll: true });
                    });
                }
            };

            if (form.dataset.buyerSectionVisible === 'true') {
                setBuyerStepVisible(true, { focus: false });
            } else {
                setBuyerStepVisible(false, { focus: false });
            }

            if (totalAmountDisplay && window.MutationObserver) {
                new MutationObserver(syncBuyerTotal).observe(totalAmountDisplay, {
                    childList: true,
                    characterData: true,
                    subtree: true,
                });
            }

            form.addEventListener('submit', function(event) {
                if (form.dataset.buyerSectionVisible !== 'true') {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();

                    if (!hasSalesItem()) {
                        showMessage('Please add at least one fish item before saving.');
                        return;
                    }

                    clearMessage();
                    setBuyerStepVisible(true);
                    return;
                }

                clearMessage();

                if (!hasSalesItem()) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    showMessage('Please add at least one fish item before saving.');
                    return;
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    form.reportValidity();
                }
            }, true);

            cancelButtons.forEach((button) => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    clearMessage();
                    setBuyerStepVisible(false, { focus: false });
                });
            });
        }

        function initializeSalesFormWhenReady(attempt = 0) {
            const root = getActiveSalesFormRoot();
            const config = typeof initializeSalesForm === 'function' ? getCurrentSalesFormConfig(root) : null;
            const ready = config
                && root.querySelector('#sales-details-container')
                && root.querySelector('#add-sales-detail-btn')
                && root.querySelector('#total-amount-display');

            if (ready) {
                initializeSalesForm(config, root);
                return;
            }

            if (attempt < 30) {
                window.requestAnimationFrame(() => initializeSalesFormWhenReady(attempt + 1));
            }
        }

        window.initializeBrokerSalesPage = function() {
            const root = getActiveSalesFormRoot();
            const scanBtn = root.querySelector('#scan-qr-btn');
            if (scanBtn && !scanBtn.dataset.salesQrBound) {
                scanBtn.dataset.salesQrBound = 'true';
                scanBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    openSalesQrScanner(scanBtn);
                });
            }

            const remoteScannerButton = root.querySelector('[data-remote-sales-scanner-open]');
            if (remoteScannerButton && !remoteScannerButton.dataset.remoteScannerLazyBound) {
                remoteScannerButton.dataset.remoteScannerLazyBound = 'true';
                remoteScannerButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    openRemoteSalesScanner(remoteScannerButton);
                });
            }

            initializeSalesFormWhenReady();
            initializeTransactionBuyerStep(root);
            if (typeof window.bindRemoteSalesScannerButtons === 'function') {
                window.bindRemoteSalesScannerButtons();
            }
            if (typeof window.resumeRemoteSalesScannerSession === 'function') {
                window.resumeRemoteSalesScannerSession();
            }
            maybeAutoPrintReceipt();
        };

        function printReceiptBroker() {
            const receiptId = document.querySelector('[data-auto-print-receipt]')?.dataset.receiptId || '';
            const receiptTitle = receiptId ? `Receipt #${receiptId}` : 'Receipt';
            window.printReceipt('receipt-content', receiptTitle);
        }

        function maybeAutoPrintReceipt() {
            const marker = document.querySelector('[data-auto-print-receipt="true"]');

            if (!marker || marker.dataset.printStarted === 'true') {
                return;
            }

            marker.dataset.printStarted = 'true';
            window.setTimeout(function() {
                if (typeof printReceiptBroker === 'function') {
                    printReceiptBroker();
                }
            }, 450);
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.initializeBrokerSalesPage();
        });
    </script>
@endsection
