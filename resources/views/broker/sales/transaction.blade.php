@php
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
    $breadcrumbs = [
        ['title' => 'Transaction'],
    ];
    $transactionUrl = route('broker.transaction');
    $salesBaseUrl = $transactionUrl;
    $salesFormConfig = [
        'fishBoxes' => $fishBoxes ?? [],
        'fishTypes' => $fishTypes ?? [],
        'fishPrices' => $fishPriceMap ?? [],
        'mode' => 'create',
        'detailIndex' => count($salesDetails ?? []),
    ];
    $printReceiptScriptUrl = asset('js/print-receipt.js') . '?v=' . filemtime(public_path('js/print-receipt.js'));
    $qrScannerLegacyScriptUrl = asset('js/qr-scanner-legacy.min.js') . '?v=' . filemtime(public_path('js/qr-scanner-legacy.min.js'));
    $salesQrScannerScriptUrl = asset('js/sales-qr-scanner.js') . '?v=' . filemtime(public_path('js/sales-qr-scanner.js'));
    $salesFormScriptUrl = asset('js/sales-form.js') . '?v=' . filemtime(public_path('js/sales-form.js'));
    $salesPageScriptUrl = asset('js/sales-page.js') . '?v=' . filemtime(public_path('js/sales-page.js'));
    $remoteSalesScannerScriptUrl = asset('js/remote-sales-scanner.js') . '?v=' . filemtime(public_path('js/remote-sales-scanner.js'));
@endphp

@extends('layouts.broker')

@section('content')
    <div
        id="sales-page-root"
        class="relative w-full content-spacing"
        data-sales-page
        data-sales-base-url="{{ $transactionUrl }}"
    >
        <div id="sales-page-fragment" data-sales-form-root>
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Transaction</h1>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:justify-end">
                    <a href="{{ route('broker.sales.sales') }}" class="app-button app-button--secondary h-12 w-full px-4 text-sm sm:w-auto">
                        <x-heroicon-o-banknotes class="h-4 w-4" />
                        <span>Sales Records</span>
                    </a>
                </div>
            </div>

            @if($brokerViewReadOnly)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Broker sales are read-only until an admin explicitly enables support actions.
                </div>
            @else
                <section class="rounded-xl bg-white p-6 shadow-lg">
                    <script type="application/json" data-sales-form-config>{!! json_encode($salesFormConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

                    <form action="{{ route('broker.sales.store') }}"
                          method="POST"
                          data-sales-async-form
                          data-sales-after-save-url="{{ $transactionUrl }}"
                          class="space-y-6">
                        @csrf
                        <input type="hidden" name="after_save" value="transaction">
                        <input type="hidden" id="sales_date" name="sales_date" value="{{ old('sales_date', date('Y-m-d')) }}">
                        <input type="hidden" id="total_amount" name="total_amount" value="{{ old('total_amount', '') }}">

                        <div class="space-y-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Sales Details <span class="text-red-500">*</span>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">Price per box auto-fills from your current broker fish price list when available.</p>
                                </div>
                                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:justify-end">
                                    <button type="button" id="add-sales-detail-btn"
                                            class="app-button app-button--dark h-12 w-full px-5 text-sm sm:w-auto">
                                        <x-heroicon-o-plus class="h-4 w-4" />
                                        <span>Add Sales Detail</span>
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

                                            <div class="min-w-[150px] flex-1">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">Price per Box</label>
                                               <input type="number" name="sales_details[{{ $index }}][unit_price]"
                                                      value="{{ $detail['unit_price'] ?? '' }}"
                                                      step="0.01" min="0"
                                                       class="unit-price-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
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
                                                       class="sub-total-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm text-gray-500"
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
                                    <span class="text-right text-2xl font-bold text-gray-900" id="total-amount-display">PHP 0.00</span>
                                </div>
                            </div>

                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="buyer_name" class="mb-2 block text-sm font-medium text-gray-700">
                                        Buyer Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="buyer_name" name="buyer_name"
                                           value="{{ old('buyer_name', '') }}"
                                           class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                           placeholder="Enter buyer name">
                                </div>

                                <div>
                                    <label for="buyer_contact" class="mb-2 block text-sm font-medium text-gray-700">Buyer Contact Number</label>
                                    <input type="text" id="buyer_contact" name="buyer_contact"
                                           value="{{ old('buyer_contact', '') }}"
                                           class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                           placeholder="Enter buyer contact">
                                </div>
                            </div>



                            <input type="hidden" id="initial_payment_date" name="initial_payment_date" value="{{ old('initial_payment_date', date('Y-m-d')) }}">

                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="initial_paid_amount" class="mb-2 block text-sm font-medium text-gray-700">Paid Amount</label>
                                   <input type="number" id="initial_paid_amount" name="initial_paid_amount"
                                          value="{{ old('initial_paid_amount', '') }}"
                                          step="0.01" min="0.01"
                                          data-currency-input="true"
                                           class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-right text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                           placeholder="0.00">
                                    <div class="mt-2 text-xs text-gray-500">
                                        Maximum payment: PHP <span id="initial-payment-max-amount" class="inline-block min-w-[4rem] text-right tabular-nums">0.00</span>
                                    </div>
                                    <div id="initial-payment-error" class="mt-2 hidden text-sm text-red-600"></div>
                                </div>

                                <div>
                                    <label for="initial_payment_method" class="mb-2 block text-sm font-medium text-gray-700">Payment Method</label>
                                    <select id="initial_payment_method" name="initial_payment_method"
                                            class="h-14 w-full rounded-2xl border border-gray-200 bg-white px-5 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
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

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <button type="submit"
                                    class="inline-flex h-12 items-center justify-center rounded-xl bg-green-600 px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-700"
                                    style="min-width: 9.5rem;">
                                Save Transaction
                            </button>
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
            <div class="flex flex-wrap gap-4">
                <div class="min-w-[200px] flex-1">
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
                <div class="min-w-[200px] flex-1">
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
                <div class="min-w-[150px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Price per Box</label>
                    <input type="number" name="sales_details[INDEX][unit_price]" step="0.01" min="0"
                           class="unit-price-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>
                <div class="min-w-[120px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">QTY</label>
                    <input type="number" name="sales_details[INDEX][quantity]" value="1" min="1"
                           class="quantity-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="1">
                </div>
                <div class="min-w-[150px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Sub Total</label>
                    <input type="number" name="sales_details[INDEX][sub_total]" step="0.01" min="0"
                           class="sub-total-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm text-gray-500"
                           readonly>
                </div>
                <div class="flex items-end">
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
    </script>
    <script src="{{ $printReceiptScriptUrl }}" defer></script>
    <script src="{{ $qrScannerLegacyScriptUrl }}" defer></script>
    <script src="{{ $salesQrScannerScriptUrl }}" defer></script>
    <script src="{{ $salesFormScriptUrl }}" defer></script>
    <script src="{{ $salesPageScriptUrl }}" defer></script>
    <script src="{{ $remoteSalesScannerScriptUrl }}" defer></script>
    <script>
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
                console.error('Unable to parse sales form config.', error);
                return null;
            }
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
            if (typeof SalesQRScanner === 'function' && !window.salesQrScanner) {
                window.salesQrScanner = new SalesQRScanner();
            }

            const root = getActiveSalesFormRoot();
            const scanBtn = root.querySelector('#scan-qr-btn');
            if (scanBtn && !scanBtn.dataset.salesQrBound) {
                scanBtn.dataset.salesQrBound = 'true';
                scanBtn.addEventListener('click', function() {
                    if (window.salesQrScanner) {
                        window.salesQrScanner.openModal();
                    } else if (window.toastr) {
                        toastr.error('QR Scanner not initialized. Please refresh the page.');
                    }
                });
            }

            initializeSalesFormWhenReady();
            if (typeof window.bindRemoteSalesScannerButtons === 'function') {
                window.bindRemoteSalesScannerButtons();
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
