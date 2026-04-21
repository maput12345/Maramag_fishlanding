@php
    $breadcrumbs = [
        ['title' => 'Sales']
    ];

    $salesBaseQuery = array_filter(
        request()->except(['modal', 'edit', 'show', 'sale', 'print']),
        fn ($value) => $value !== null && $value !== ''
    );

    $salesBaseUrl = route('broker.sales.sales', $salesBaseQuery);

    $salesModalBreadcrumbs = [
        'create' => 'Create Sale',
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
    <div
        id="sales-page-root"
        class="relative w-full content-spacing workspace-modal-host"
        data-sales-page
        data-sales-base-url="{{ $salesBaseUrl }}"
    >
        <div class="mb-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Sales</h1>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'create'])) }}"
                       data-sales-modal-link
                       class="flex w-full items-center justify-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-green-700 sm:w-auto sm:px-4">
                        <x-heroicon-o-plus class="mr-2 h-4 w-4" />
                        Create Sale
                    </a>
                </div>
            </div>
        </div>

        <div id="sales-page-fragment">

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Sales Records</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($salesSummary['count'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Gross Amount</p>
                <p class="mt-2 text-3xl font-bold text-blue-600">PHP {{ number_format($salesSummary['gross_total'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Collected</p>
                <p class="mt-2 text-3xl font-bold text-green-600">PHP {{ number_format($salesSummary['paid_total'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Outstanding</p>
                <p class="mt-2 text-3xl font-bold text-orange-600">PHP {{ number_format($salesSummary['balance_total'] ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="mb-6 rounded-xl bg-white p-4 shadow-lg">
            <form method="GET" action="{{ route('broker.sales.sales') }}" x-data="{
                search: '{{ request('search') }}',
                status: '{{ request('status') }}',
                dateFrom: '{{ request('date_from') }}',
                dateTo: '{{ request('date_to') }}'
            }">
                <div class="sales-filter-layout">
                    <div class="search-field">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <input type="text"
                                   name="search"
                                   x-model="search"
                                   placeholder="Search buyer name or contact..."
                                   class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <div class="status-field">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" x-model="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            @foreach($salesStatusesWithDisplayNames as $status => $displayName)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ $displayName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fish-type-field">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Date From</label>
                        <input type="date"
                               name="date_from"
                               x-model="dateFrom"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="fish-type-field">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Date To</label>
                        <input type="date"
                               name="date_to"
                               x-model="dateTo"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="buttons-field flex justify-end space-x-2">
                        <a href="{{ route('broker.sales.sales') }}"
                           class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 lg:px-4">
                            Clear
                        </a>
                        <button type="submit"
                                class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 lg:px-4">
                            Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="mb-4">
            <p class="text-sm text-gray-600">
                Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} sales
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <span class="text-blue-600">(filtered)</span>
                @endif
            </p>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Buyer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Paid Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $sale->sales_date->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $sale->formatted_items }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $sale->buyer_name }}</div>
                                        <div class="text-sm text-gray-500">{{ $sale->buyer_contact }}</div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    PHP {{ number_format($sale->total_amount, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    PHP {{ number_format($sale->paid_amount, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $salesStatusesWithColorClasses[$sale->status] }}">
                                        {{ $salesStatusesWithDisplayNames[$sale->status] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'show', 'show' => $sale->id])) }}"
                                           data-sales-modal-link
                                           class="text-green-600 transition-colors hover:text-green-900"
                                           title="View Sale">
                                            <x-heroicon-o-eye class="h-5 w-5" />
                                        </a>
                                        <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'print', 'print' => $sale->id])) }}"
                                           data-sales-modal-link
                                           class="text-purple-600 transition-colors hover:text-purple-900"
                                           title="Print Receipt">
                                            <x-heroicon-o-printer class="h-5 w-5" />
                                        </a>
                                        @if($sale->status !== \App\Constants\SalesStatusConstant::PAID)
                                            <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'edit', 'edit' => $sale->id])) }}"
                                               data-sales-modal-link
                                               class="text-blue-600 transition-colors hover:text-blue-900"
                                               title="Edit Sale">
                                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                                            </a>
                                            <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'payment', 'sale' => $sale->id])) }}"
                                               data-sales-modal-link
                                               class="text-green-600 transition-colors hover:text-green-900"
                                               title="Add Payment">
                                                <x-heroicon-o-currency-dollar class="h-5 w-5" />
                                            </a>
                                        @endif
                                        <form action="{{ route('broker.sales.destroy', $sale->id) }}" method="POST" class="inline-block" data-swal="delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 transition-colors hover:text-red-900"
                                                    title="Delete Sale">
                                                <x-heroicon-o-trash class="inline h-5 w-5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <x-heroicon-o-shopping-cart class="mb-4 h-16 w-16 text-gray-400" />
                                        <h3 class="mb-2 text-lg font-medium text-gray-900">No sales found</h3>
                                        <p class="mb-6 text-gray-500">Get started by creating your first sale.</p>
                                        <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'create'])) }}"
                                           data-sales-modal-link
                                           class="inline-flex items-center space-x-2 rounded-lg bg-green-600 px-6 py-3 font-medium text-white transition-colors hover:bg-green-700">
                                            <x-heroicon-o-plus class="h-5 w-5" />
                                            <span>Create Sale</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($sales->hasPages())
            <div class="mt-8">
                {{ $sales->appends(request()->query())->links('components.pagination') }}
            </div>
        @endif
        @include('broker.sales.partials.create-edit-modal')
        @include('broker.sales.partials.payment-modal')
        @include('broker.sales.partials.print-modal')
        @include('broker.sales.partials.show-modal-polished')
        </div>
    </div>

    <template id="sales-detail-row-template">
        <div class="sales-detail-row rounded-xl border border-gray-200 bg-gray-50 p-6">
            <div class="flex flex-wrap gap-4">
                <div class="min-w-[200px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Fish Type</label>
                    <select name="sales_details[INDEX][fish_type_id]"
                            class="fish-type-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Select Fish Type</option>
                        @foreach($fishTypes ?? [] as $fishType)
                            <option value="{{ $fishType->id }}">{{ $fishType->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-[200px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Fish Box</label>
                    <div class="fish-boxes-container max-h-32 space-y-1 overflow-y-auto">
                        <div class="fish-box-item mb-1">
                            <select class="fish-box-select w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm" disabled>
                                <option value="">Auto-selected</option>
                            </select>
                            <input type="hidden" name="sales_details[INDEX][box_id][]" class="fish-box-hidden-input">
                        </div>
                    </div>
                </div>

                <div class="min-w-[150px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Unit Price</label>
                    <input type="number" name="sales_details[INDEX][unit_price]" step="0.01" min="0"
                           class="unit-price-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>

                <div class="min-w-[120px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">QTY</label>
                    <input type="number" name="sales_details[INDEX][quantity]" value="1" min="1"
                           class="quantity-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="1">
                </div>

                <div class="min-w-[150px] flex-1">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Sub Total</label>
                    <input type="number" name="sales_details[INDEX][sub_total]" step="0.01" min="0"
                           class="sub-total-input w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm"
                           readonly>
                </div>

                <div class="flex-shrink-0">
                    <label class="mb-2 block text-sm font-medium text-gray-700">&nbsp;</label>
                    <button type="button" class="remove-detail-btn rounded-lg p-2 text-red-600 transition-colors hover:bg-red-50 hover:text-red-800">
                        <x-heroicon-o-trash class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <input type="hidden" name="sales_details[INDEX][item]" class="item-input" value="">
            <input type="hidden" name="sales_details[INDEX][item_description]" class="item-description-input" value="">
        </div>
    </template>

    <script src="{{ asset('js/print-receipt.js') }}" defer></script>
    <script src="{{ asset('js/sales-qr-scanner.js') }}" defer></script>
    <script src="{{ asset('js/sales-form.js') }}" defer></script>
    <script src="{{ asset('js/sales-page.js') }}" defer></script>
    <script>
        window.initializeBrokerSalesPage = function(scope = document) {
            if (typeof SalesQRScanner === 'function' && !window.salesQrScanner) {
                window.salesQrScanner = new SalesQRScanner();
            }

            const scanBtn = document.getElementById('scan-qr-btn');
            if (scanBtn && !scanBtn.dataset.salesQrBound) {
                scanBtn.dataset.salesQrBound = 'true';
                scanBtn.addEventListener('click', function() {
                    if (window.salesQrScanner) {
                        window.salesQrScanner.openModal();
                    } else {
                        toastr.error('QR Scanner not initialized. Please refresh the page.');
                    }
                });
            }

            if (typeof initializeSalesForm === 'function') {
                initializeSalesForm({
                    fishBoxes: @json($fishBoxes ?? []),
                    fishTypes: @json($fishTypes ?? []),
                    fishPrices: @json($fishPriceMap ?? []),
                    detailIndex: {{ count($salesDetails) }}
                });
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            window.initializeBrokerSalesPage(document);
        });

        function paymentForm() {
            return {
                paidAmount: 0,
                maxPaymentAmount: {{ $saleForPayment ? $saleForPayment->remaining_amount : 0 }},
                paymentError: '',

                initializePaymentForm() {
                },

                validatePaymentAmount() {
                    this.paymentError = '';

                    if (this.paidAmount > this.maxPaymentAmount) {
                        this.paymentError = 'Payment amount cannot exceed the remaining balance of PHP ' + this.maxPaymentAmount.toFixed(2);
                        return false;
                    }

                    if (this.paidAmount <= 0) {
                        this.paymentError = 'Payment amount must be greater than 0';
                        return false;
                    }

                    return true;
                }
            }
        }

        function printReceiptBroker() {
            const receiptTitle = 'Receipt #{{ $printingSales ? $printingSales->id : "" }}';
            window.printReceipt('receipt-content', receiptTitle);
        }
    </script>
@endsection
