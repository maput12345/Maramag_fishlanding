@php
$breadcrumbs = [
        ['title' => 'Sales']
    ];

    $salesModalBreadcrumbs = [
        'create' => 'Transaction',
        'edit' => 'Edit Sale',
        'show' => 'View Sale',
        'payment' => 'Add Payment',
        'print' => 'Print Receipt',
    ];

    if (request()->filled('modal') && isset($salesModalBreadcrumbs[request('modal')])) {
        $breadcrumbs[] = ['title' => $salesModalBreadcrumbs[request('modal')]];
    }
@endphp
@extends('layouts.broker')

@section('content')
            <div class="relative w-full content-spacing workspace-modal-host">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-lg p-5">
                        <p class="text-sm font-medium text-gray-500">Sales Records</p>
                        <p class="summary-stat-value text-gray-900">{{ number_format($salesSummary['count'] ?? 0) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-5">
                        <p class="text-sm font-medium text-gray-500">Sales Amount</p>
                        <p class="summary-stat-value text-right text-blue-600">₱{{ number_format($salesSummary['gross_total'] ?? 0, 2) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-5">
                        <p class="text-sm font-medium text-gray-500">Total Collection</p>
                        <p class="summary-stat-value text-right text-green-600">₱{{ number_format($salesSummary['paid_total'] ?? 0, 2) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-5">
                        <p class="text-sm font-medium text-gray-500">Outstanding Balance</p>
                        <p class="summary-stat-value text-right text-orange-600">₱{{ number_format($salesSummary['balance_total'] ?? 0, 2) }}</p>
                    </div>
                </div>

                <!-- Sales Filters -->
                <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
                    <form method="GET" action="{{ route('broker.sales.sales') }}" x-data="{
                        search: '{{ request('search') }}',
                        status: '{{ request('status') }}',
                        dateFrom: '{{ $dateFrom ?? request('date_from') }}',
                        dateTo: '{{ $dateTo ?? request('date_to') }}'
                    }">
                        <div class="sales-filter-layout">
                            <!-- Search Field -->
                            <div class="search-field">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <div class="relative">
                                    <input type="text"
                                        name="search"
                                        x-model="search"
                                        placeholder="Search buyer name or contact..."
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="status-field">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" x-model="status" class="app-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Status</option>
                                    @foreach($salesStatusesWithDisplayNames as $status => $displayName)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ $displayName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Date From -->
                            <div class="fish-type-field">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date"
                                    name="date_from"
                                    x-model="dateFrom"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Date To -->
                            <div class="fish-type-field">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date"
                                    name="date_to"
                                    x-model="dateTo"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Action Buttons -->
                            <div class="buttons-field filter-action-group justify-end">
                                <a href="{{ route('broker.sales.sales') }}"
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
                        Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} sales
                        @if(($dateFrom ?? null) && ($dateTo ?? null))
                            <span class="text-gray-500">for {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') }}{{ $dateFrom !== $dateTo ? ' - ' . \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') : '' }}</span>
                        @endif
                        @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                            <span class="text-blue-600">(filtered)</span>
                        @endif
                    </p>
                </div>

                <!-- Sales Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-28 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commodities</th>
                                    <th class="w-44 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buyer</th>
                                    <th class="w-32 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="w-32 px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                    <th class="w-36 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="w-40 px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($sales as $sale)
                                    <tr class="group hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $sale->sales_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-900">
                                            {{ $sale->formatted_items }}
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-medium text-gray-900" title="{{ $sale->buyer_name }}">{{ $sale->buyer_name }}</div>
                                                <div class="truncate text-sm text-gray-500" title="{{ $sale->buyer_contact }}">{{ $sale->buyer_contact }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm tabular-nums text-gray-900">
                                            ₱{{ number_format($sale->total_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm tabular-nums text-gray-900">
                                            ₱{{ number_format($sale->paid_amount, 2) }}
                                        </td>
                                        <td class="px-4 py-4">
                                            <x-status-badge :status="$salesStatusesWithDisplayNames[$sale->status]" />
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('broker.sales.sales', ['modal' => 'show', 'show' => $sale->id]) }}"
                                                   class="text-green-600 hover:text-green-900 transition-colors"
                                                   title="View Sale">
                                                    <x-heroicon-o-eye class="w-5 h-5" />
                                                </a>
                                                <a href="{{ route('broker.sales.sales', ['modal' => 'print', 'print' => $sale->id]) }}"
                                                   class="text-purple-600 hover:text-purple-900 transition-colors"
                                                   title="Print Receipt">
                                                    <x-heroicon-o-printer class="w-5 h-5" />
                                                </a>
                                                @if($sale->status !== \App\Constants\SalesStatusConstant::PAID)
                                                <a href="{{ route('broker.sales.sales', ['modal' => 'edit', 'edit' => $sale->id]) }}"
                                                   class="text-blue-600 hover:text-blue-900 transition-colors"
                                                   title="Edit Sale">
                                                    <x-heroicon-o-pencil-square class="w-5 h-5" />
                                                </a>
                                                <a href="{{ route('broker.sales.sales', ['modal' => 'payment', 'sale' => $sale->id]) }}"
                                                   class="text-green-600 hover:text-green-900 transition-colors"
                                                   title="Add Payment">
                                                    <x-heroicon-o-currency-dollar class="w-5 h-5" />
                                                </a>
                                                @endif
                                                <form action="{{ route('broker.sales.destroy', $sale->id) }}" method="POST" class="inline-block" data-swal="delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-900 transition-colors"
                                                            title="Delete Sale">
                                                        <x-heroicon-o-trash class="w-5 h-5 inline" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <x-heroicon-o-shopping-cart class="w-16 h-16 text-gray-400 mb-4" />
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">No sales found</h3>
                                                <p class="text-gray-500 mb-6">Get started by creating your first sale.</p>
                                                <a href="{{ route('broker.transaction') }}"
                                                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center space-x-2">
                                                    <x-heroicon-o-plus class="w-5 h-5" />
                                                    <span>Transaction</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $sales->appends(request()->query())->links('components.pagination') }}
                </div>
{{-- Modals --}}
@include('broker.sales.partials.create-edit-modal')
@include('broker.sales.partials.payment-modal')
@include('broker.sales.partials.print-modal')
@include('broker.sales.partials.show-modal-polished')
            </div>

{{-- Hidden template for new sales detail rows (used by JavaScript) --}}
<template id="sales-detail-row-template">
    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 sales-detail-row">
        <div class="flex flex-wrap gap-3 xl:flex-nowrap xl:items-start">
            <!-- Fish Type Selection -->
            <div class="w-full lg:w-52 lg:flex-none">
                <label class="block text-sm font-medium text-gray-700 mb-2">Fish</label>
                <select name="sales_details[INDEX][fish_type_id]"
                        class="fish-type-select w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                    <option value="">Select Fish</option>
                    @foreach($fishTypes ?? [] as $fishType)
                        <option value="{{ $fishType->id }}">{{ $fishType->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Fish Box Selection (Auto-populated, disabled) -->
            <div class="w-full lg:w-52 lg:flex-none">
                <label class="block text-sm font-medium text-gray-700 mb-2">Fish Box</label>
                <div class="fish-boxes-container space-y-1 max-h-32 overflow-y-auto">
                    <div class="fish-box-item mb-1">
                        <select class="fish-box-select w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-100 cursor-not-allowed" disabled>
                            <option value="">Auto-assign available box</option>
                        </select>
                        <input type="hidden" name="sales_details[INDEX][box_id][]" class="fish-box-hidden-input">
                    </div>
                </div>
            </div>

            <!-- Unit Price -->
            <div class="w-full lg:w-36 lg:flex-none">
                <label class="block text-sm font-medium text-gray-700 mb-2">Price per Box</label>
                <input type="text" name="sales_details[INDEX][unit_price]" inputmode="decimal"
                       class="unit-price-input w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-right text-sm tabular-nums text-gray-500"
                       placeholder="0.00"
                       readonly>
            </div>

            <!-- Discount Type -->
            <div class="w-full lg:w-32 lg:flex-none">
                <label class="block text-sm font-medium text-gray-700 mb-2">Discount Type</label>
                <select name="sales_details[INDEX][discount_mode]"
                        class="discount-mode-select w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    <option value="percent" selected>Percent</option>
                    <option value="amount">Amount</option>
                </select>
            </div>

            <!-- Discount -->
            <div class="w-full lg:w-36 lg:flex-none">
                <label class="discount-value-label block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                <input type="text" name="sales_details[INDEX][discount_value]" inputmode="decimal"
                       class="discount-value-input w-full px-3 py-2 border border-gray-300 rounded-lg text-right text-sm tabular-nums focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="0.00">
                <input type="hidden" name="sales_details[INDEX][discount_percent]" class="discount-percent-input">
                <input type="hidden" name="sales_details[INDEX][discount]" class="discount-input">
            </div>

            <!-- Quantity -->
            <div class="w-full lg:w-20 lg:flex-none">
                <label class="block text-sm font-medium text-gray-700 mb-2">QTY</label>
                <input type="number" name="sales_details[INDEX][quantity]" value="1" min="1"
                       class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="1">
            </div>

            <!-- Sub Total (Auto-calculated, disabled) -->
            <div class="w-full lg:w-36 lg:flex-none">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sub Total</label>
                <input type="text" name="sales_details[INDEX][sub_total]" inputmode="decimal"
                       class="sub-total-input w-full px-3 py-2 border border-gray-300 rounded-lg text-right text-sm tabular-nums bg-gray-100 cursor-not-allowed"
                       readonly>
            </div>

            <!-- Remove Button -->
            <div class="flex-shrink-0">
                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                <button type="button" class="remove-detail-btn text-red-600 hover:text-red-800 transition-colors p-2 rounded-lg hover:bg-red-50">
                    <x-heroicon-o-trash class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Hidden fields for item and description -->
        <input type="hidden" name="sales_details[INDEX][item]" class="item-input" value="">
        <input type="hidden" name="sales_details[INDEX][item_description]" class="item-description-input" value="">
    </div>
</template>

<script>
    window.salesQrScannerConfig = {
        lookupUrlTemplate: @json(route('broker.fish-boxes.qr', ['qrCode' => '__QR_CODE__']))
    };
</script>
<script src="{{ asset('js/print-receipt.js') }}" defer></script>
<script src="{{ asset('js/qr-scanner-legacy.min.js') }}" defer></script>
<script src="{{ asset('js/sales-qr-scanner.js') }}" defer></script>
<script src="{{ asset('js/sales-form.js') }}" defer></script>
<script>
// Initialize sales form and QR scanner when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sales QR Scanner
    window.salesQrScanner = new SalesQRScanner();

    // Setup QR scan button event listener
    const scanBtn = document.getElementById('scan-qr-btn');
    if (scanBtn) {
        scanBtn.addEventListener('click', function() {
            if (window.salesQrScanner) {
                window.salesQrScanner.openModal();
            } else {
                toastr.error('QR Scanner not initialized. Please refresh the page.');
            }
        });
    }

    // Initialize sales form
    if (typeof initializeSalesForm === 'function') {
        initializeSalesForm({
            fishBoxes: @json($fishBoxes ?? []),
            fishTypes: @json($fishTypes ?? []),
            fishPrices: @json($fishPriceMap ?? []),
            detailIndex: {{ count($salesDetails) }}
        });
    }
});

function paymentForm(config = {}) {
    return {
        paidAmount: Number(config.initialPaidAmount ?? 0) > 0
            ? Number(config.initialPaidAmount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '',
        maxPaymentAmount: Number(config.maxPaymentAmount ?? {{ $saleForPayment ? $saleForPayment->remaining_amount : 0 }}),
        paymentMethod: config.initialPaymentMethod ?? '',
        paymentError: '',

        initializePaymentForm() {
            // Initialize form
        },

        parseMoney(value) {
            const normalizedValue = String(value ?? '').replace(/[₱,\s]/g, '');
            const parsedValue = parseFloat(normalizedValue);

            return Number.isFinite(parsedValue) ? parsedValue : 0;
        },

        formatMoney(value) {
            return (Number(value) || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        formatPaymentAmount() {
            if (this.paidAmount === '') {
                return;
            }

            this.paidAmount = this.formatMoney(this.parseMoney(this.paidAmount));
            this.validatePaymentAmount();
        },

        normalizePaymentAmount() {
            this.paidAmount = this.parseMoney(this.paidAmount).toFixed(2);
        },

        requiresReferenceNumber() {
            return ['GCash', 'Bank Transfer'].includes(this.paymentMethod);
        },

        validatePaymentAmount() {
            this.paymentError = '';
            const currentAmount = this.parseMoney(this.paidAmount);

            if (currentAmount > this.maxPaymentAmount) {
                this.paymentError = 'Payment amount cannot exceed the remaining balance of ₱' + this.formatMoney(this.maxPaymentAmount);
                return false;
            }

            if (currentAmount <= 0) {
                this.paymentError = 'Payment amount must be greater than 0';
                return false;
            }

            return true;
        }
    }
}

// Print receipt function - calls external print-receipt.js
function printReceiptBroker() {
    const receiptTitle = 'Receipt #{{ $printingSales ? $printingSales->id : "" }}';
    window.printReceipt('receipt-content', receiptTitle);
}

</script>
@endsection
