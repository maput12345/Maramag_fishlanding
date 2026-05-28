@php
$isCashierStaff = auth()->check() && auth()->user()->isCashier();
$brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
    $breadcrumbs = [
        ['title' => 'Sales']
    ];

    $salesBaseQuery = array_filter(
        request()->except(['modal', 'edit', 'show', 'sale', 'print']),
        fn ($value) => $value !== null && $value !== ''
    );

    $salesBaseUrl = route('broker.sales.sales', $salesBaseQuery);
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

    $reportDateLabel = ($dateFrom ?? null) && ($dateTo ?? null)
        ? \Illuminate\Support\Carbon::parse($dateFrom)->format('M d, Y') . (($dateFrom !== $dateTo) ? ' - ' . \Illuminate\Support\Carbon::parse($dateTo)->format('M d, Y') : '')
        : 'All Dates';

    $printReceiptScriptUrl = asset('js/print-receipt.js') . '?v=' . filemtime(public_path('js/print-receipt.js'));
    $qrScannerLegacyScriptUrl = asset('js/qr-scanner-legacy.min.js') . '?v=' . filemtime(public_path('js/qr-scanner-legacy.min.js'));
    $salesQrScannerScriptUrl = asset('js/sales-qr-scanner.js') . '?v=' . filemtime(public_path('js/sales-qr-scanner.js'));
    $salesFormScriptUrl = asset('js/sales-form.js') . '?v=' . filemtime(public_path('js/sales-form.js'));
    $salesPageScriptUrl = asset('js/sales-page.js') . '?v=' . filemtime(public_path('js/sales-page.js'));
@endphp
@extends('layouts.broker')

@section('content')
    @if($isCashierStaff)
        <style>
            @media (min-width: 768px) {
                .sales-filter-layout--cashier {
                    grid-template-columns: minmax(320px, 1fr) minmax(220px, 280px) auto;
                }

                .sales-filter-layout--cashier .search-field,
                .sales-filter-layout--cashier .status-field,
                .sales-filter-layout--cashier .buttons-field {
                    grid-column: auto;
                }

                .sales-filter-layout--cashier .buttons-field {
                    align-self: end;
                    justify-content: flex-end;
                    white-space: nowrap;
                }
            }
        </style>
    @endif

    <div
        id="sales-page-root"
        class="relative w-full content-spacing workspace-modal-host"
        data-sales-page
        data-sales-records
        data-sales-base-url="{{ $salesBaseUrl }}"
    >

        <div id="sales-page-fragment">

        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ $reportDateLabel }}</h2>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Sales Records</p>
                <p class="summary-stat-value text-gray-900">{{ number_format($salesSummary['count'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Sales Amount</p>
                <p class="summary-stat-value text-right text-blue-600">₱{{ number_format($salesSummary['gross_total'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Total Collection</p>
                <p class="summary-stat-value text-right text-green-600">₱{{ number_format($salesSummary['paid_total'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-lg">
                <p class="text-sm font-medium text-gray-500">Total Outstanding Balance</p>
                <p class="summary-stat-value text-right text-orange-600">₱{{ number_format($totalOutstandingBalance ?? ($salesSummary['balance_total'] ?? 0), 2) }}</p>
            </div>
        </div>

        <div class="mb-6 rounded-xl bg-white p-4 shadow-lg">
            <form method="GET" action="{{ route('broker.sales.sales') }}" x-data="{
                search: '{{ request('search') }}',
                status: '{{ request('status') }}',
                dateFrom: '{{ request('date_from') }}',
                dateTo: '{{ request('date_to') }}'
            }">
                <div class="sales-filter-layout {{ $isCashierStaff ? 'sales-filter-layout--cashier' : '' }}">
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
                        <select name="status" x-model="status" class="app-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            @foreach($salesStatusesWithDisplayNames as $status => $displayName)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ $displayName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @unless($isCashierStaff)
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
                    @endunless

                    <div class="buttons-field flex flex-col items-center justify-end gap-3 sm:flex-row">
                        <a href="{{ route('broker.sales.sales') }}"
                           class="btn-clear w-full sm:w-auto">
                            Clear
                        </a>
                        <button type="submit"
                                class="btn-search w-full sm:w-auto">
                            Search
                        </button>
                        @unless($isCashierStaff)
                            <button type="button"
                                    class="app-button app-button--dark w-full px-4 py-2 text-sm sm:w-auto"
                                    onclick="printDailySalesReport()"
                                    @disabled(($reportSales ?? collect())->isEmpty())>
                                <x-heroicon-o-printer class="h-4 w-4" />
                                <span>Print</span>
                            </button>
                        @endunless
                    </div>
                </div>
            </form>
        </div>

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

        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-28 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Commodities</th>
                            <th class="w-44 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Buyer</th>
                            @unless($isCashierStaff)
                                <th class="w-52 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Processed By</th>
                            @endunless
                            <th class="w-32 px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            <th class="w-32 px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Paid</th>
                            <th class="w-36 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="w-40 px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sales as $sale)
                            <tr class="group hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900">
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
                                @unless($isCashierStaff)
                                    <td class="px-4 py-4">
                                        @php
                                            $creator = $sale->creator;
                                            $processedByLabel = $creator?->isCashier()
                                                ? 'Cashier'
                                                : 'Broker Owner';
                                            $processedByName = $creator?->name ?? 'Broker Owner';
                                        @endphp
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-gray-900" title="{{ $processedByName }}">{{ $processedByName }}</div>
                                            <div class="truncate text-xs text-gray-500">{{ $processedByLabel }}</div>
                                        </div>
                                    </td>
                                @endunless
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-gray-900">
                                    ₱{{ number_format($sale->total_amount, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-gray-900">
                                    ₱{{ number_format($sale->paid_amount, 2) }}
                                </td>
                                <td class="px-4 py-4">
                                    <x-status-badge :status="$salesStatusesWithDisplayNames[$sale->status]" />
                                </td>
                                <td class="px-4 py-4 text-sm font-medium">
                                    <div class="flex items-center justify-center gap-2">
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
                                        @php
                                            $cashierCanModifySale = ! $isCashierStaff
                                                || (
                                                    (int) $sale->created_by_user_id === (int) auth()->id()
                                                    && $sale->sales_date?->isSameDay(today())
                                                    && $sale->status !== \App\Constants\SalesStatusConstant::PAID
                                                );
                                        @endphp
                                        @if(!$brokerViewReadOnly && $sale->status !== \App\Constants\SalesStatusConstant::PAID && $cashierCanModifySale)
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
                                        @unless($brokerViewReadOnly || $isCashierStaff)
                                            <form action="{{ route('broker.sales.destroy', $sale->id) }}" method="POST" class="inline-block" data-swal="delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 transition-colors hover:text-red-900"
                                                        title="Delete Sale">
                                                    <x-heroicon-o-trash class="inline h-5 w-5" />
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                    <td colspan="{{ $isCashierStaff ? 7 : 8 }}" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <x-heroicon-o-shopping-cart class="mb-4 h-16 w-16 text-gray-400" />
                                        <h3 class="mb-2 text-lg font-medium text-gray-900">No sales found</h3>
                                        <p class="mb-6 text-gray-500">
                                            {{ $brokerViewReadOnly ? 'No sales matched the current filters for this broker.' : 'Get started by creating your first sale.' }}
                                        </p>
                                        @unless($brokerViewReadOnly)
                                            <a href="{{ route('broker.transaction') }}"
                                               class="inline-flex items-center space-x-2 rounded-lg bg-green-600 px-6 py-3 font-medium text-white transition-colors hover:bg-green-700">
                                                <x-heroicon-o-plus class="h-5 w-5" />
                                                <span>Transaction</span>
                                            </a>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8">
            {{ $sales->appends(request()->query())->links('components.pagination') }}
        </div>
        @include('broker.sales.partials.create-edit-modal')
        @include('broker.sales.partials.payment-modal')
        @include('broker.sales.partials.print-modal')
        @include('broker.sales.partials.show-modal-polished')
        </div>
    </div>

    @unless($isCashierStaff)
        <div id="daily-sales-report"
             data-watermark-logo-url="{{ asset('image/logo.png') }}"
             hidden>
            <div style="font-family: Arial, sans-serif; color: #111827;">
                <div style="text-align: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 14px; margin-bottom: 16px;">
                    <h1 style="margin: 0; font-size: 22px;">Daily Sales and Sold Boxes Report</h1>
                    <p style="margin: 6px 0 0; font-size: 13px; color: #4b5563;">{{ $broker?->name ?? auth()->user()?->name }}</p>
                    @if($broker?->stall_name)
                        <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">{{ $broker->stall_name }}</p>
                    @endif
                    <p style="margin: 8px 0 0; font-size: 12px; color: #6b7280;">Date: {{ $reportDateLabel }}</p>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                    <tr>
                        <td style="border: 1px solid #e5e7eb; padding: 10px;">
                            <strong>Sales Records</strong><br>{{ number_format($salesSummary['count'] ?? 0) }}
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 10px;">
                            <strong>Sold Boxes</strong><br>{{ number_format($reportSoldBoxCount ?? 0) }}
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 10px;">
                            <strong>Sales Amount</strong><br>PHP {{ number_format($salesSummary['gross_total'] ?? 0, 2) }}
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 10px;">
                            <strong>Total Collection</strong><br>PHP {{ number_format($salesSummary['paid_total'] ?? 0, 2) }}
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 10px;">
                            <strong>Balance</strong><br>PHP {{ number_format($salesSummary['balance_total'] ?? 0, 2) }}
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Date</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Transaction</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Buyer</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Fish Box</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Fish</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">Price</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($reportSales ?? collect()) as $reportSale)
                            @foreach($reportSale->salesDetails as $detail)
                                @php
                                    $reportFishBox = $detail->fishBoxPurchase?->fishBox;
                                    $reportFish = $detail->fishBoxPurchase?->fishType;
                                @endphp
                                <tr>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ $reportSale->sales_date?->format('M d, Y') }}</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px;">#{{ $reportSale->id }}</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ $reportSale->buyer_name }}</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ $reportFishBox?->name ?? 'Unassigned' }}</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ \App\Models\BrokerFishTypeAssignment::resolveDisplayName($reportSale->broker_id, $reportFish) ?? 'Unassigned' }}</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">PHP {{ number_format((float) $detail->unit_price, 2) }}</td>
                                    <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ $salesStatusesWithDisplayNames[$reportSale->status] ?? $reportSale->status }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" style="border: 1px solid #e5e7eb; padding: 16px; text-align: center; color: #6b7280;">No sales found for this report.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless

    <template id="sales-detail-row-template">
        <div class="sales-detail-row rounded-2xl border border-gray-200 bg-white/80 p-6">
            <div class="flex flex-wrap gap-3 xl:flex-nowrap xl:items-start">
                <div class="w-full lg:w-52 lg:flex-none">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Fish</label>
                    <select name="sales_details[INDEX][fish_type_id]"
                            class="fish-type-select h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Select Fish</option>
                        @foreach($fishTypes ?? [] as $fishType)
@php
$suggestedPrice = $fishPriceMap[(string) $fishType->id] ?? $fishPriceMap[$fishType->id] ?? null;
                            @endphp
<option value="{{ $fishType->id }}"
                                    data-suggested-price="{{ $suggestedPrice !== null ? $suggestedPrice : '' }}">
                                {{ $fishType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full lg:w-52 lg:flex-none">
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

                <div class="w-full lg:w-36 lg:flex-none">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Price per Box</label>
                    <input type="text" name="sales_details[INDEX][unit_price]" inputmode="decimal"
                           class="unit-price-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-gray-50 px-4 text-right text-sm tabular-nums text-gray-500"
                           placeholder="0.00"
                           readonly>
                </div>

                <div class="w-full lg:w-32 lg:flex-none">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Discount Type</label>
                    <select name="sales_details[INDEX][discount_mode]"
                            class="discount-mode-select h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        <option value="percent" selected>Percent</option>
                        <option value="amount">Amount</option>
                    </select>
                </div>

                <div class="w-full lg:w-36 lg:flex-none">
                    <label class="discount-value-label mb-2 block text-sm font-medium text-gray-700">Discount %</label>
                    <input type="text" name="sales_details[INDEX][discount_value]" inputmode="decimal"
                           class="discount-value-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm tabular-nums text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                    <input type="hidden" name="sales_details[INDEX][discount_percent]" class="discount-percent-input">
                    <input type="hidden" name="sales_details[INDEX][discount]" class="discount-input">
                </div>

                <div class="w-full lg:w-20 lg:flex-none">
                    <label class="mb-2 block text-sm font-medium text-gray-700">QTY</label>
                    <input type="number" name="sales_details[INDEX][quantity]" value="1" min="1"
                           class="quantity-input h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                           placeholder="1">
                </div>

                <div class="w-full lg:w-36 lg:flex-none">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Sub Total</label>
                    <input type="text" name="sales_details[INDEX][sub_total]" inputmode="decimal"
                           class="sub-total-input h-12 w-full cursor-not-allowed rounded-2xl border border-gray-200 bg-white px-4 text-right text-sm tabular-nums text-gray-500"
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
    </script>
    <script src="{{ $printReceiptScriptUrl }}" defer></script>
    <script src="{{ $qrScannerLegacyScriptUrl }}" defer></script>
    <script src="{{ $salesQrScannerScriptUrl }}" defer></script>
    <script src="{{ $salesFormScriptUrl }}" defer></script>
    <script src="{{ $salesPageScriptUrl }}" defer></script>
    <script>
        function getActiveSalesModalRoot() {
            const modalRoots = Array.from(document.querySelectorAll('[data-app-modal-root]'));

            return modalRoots.reverse().find((modalRoot) => modalRoot.offsetParent !== null)
                || modalRoots.at(-1)
                || null;
        }

        function printDailySalesReport() {
            window.printReceipt('daily-sales-report', 'Daily Sales Report');
        }

        function getCurrentSalesFormConfig(modalRoot = getActiveSalesModalRoot()) {
            const configNode = modalRoot?.querySelector('[data-sales-form-config]');

            if (!configNode) {
                return null;
            }

            try {
                return JSON.parse(configNode.textContent);
            } catch (error) {
                return null;
            }
        }

        function applySalesPriceFallback(modalRoot = getActiveSalesModalRoot()) {
            const config = getCurrentSalesFormConfig(modalRoot);
            const fishPrices = config?.fishPrices || {};

            if (!modalRoot || !fishPrices || typeof fishPrices !== 'object') {
                return;
            }

            modalRoot.querySelectorAll('.sales-detail-row').forEach((row) => {
                const fishTypeSelect = row.querySelector('.fish-type-select');
                const unitPriceInput = row.querySelector('.unit-price-input');

                if (!fishTypeSelect || !unitPriceInput || !fishTypeSelect.value) {
                    return;
                }

                const selectedOption = fishTypeSelect.selectedOptions?.[0];
                const suggestedPrice = fishPrices[String(fishTypeSelect.value)]
                    ?? selectedOption?.dataset?.suggestedPrice;
                const parsedPrice = Number(suggestedPrice);
                const currentPrice = Number(unitPriceInput.value);
                const canFillPrice = Number.isFinite(parsedPrice)
                    && (unitPriceInput.value === '' || currentPrice === 0);

                if (!canFillPrice) {
                    return;
                }

                unitPriceInput.value = parsedPrice.toFixed(2);
                unitPriceInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }

        function initializeSalesFormWhenReady(attempt = 0) {
            const maxAttempts = 30;
            const hasInitializer = typeof initializeSalesForm === 'function';
            const modalRoot = getActiveSalesModalRoot();
            const config = hasInitializer ? getCurrentSalesFormConfig(modalRoot) : null;
            const container = modalRoot?.querySelector('#sales-details-container');
            const addBtn = modalRoot?.querySelector('#add-sales-detail-btn');
            const totalAmountDisplay = modalRoot?.querySelector('#total-amount-display');

            if (hasInitializer && config && container && addBtn && totalAmountDisplay && modalRoot) {
                initializeSalesForm(config, modalRoot);
                applySalesPriceFallback(modalRoot);
                return;
            }

            if (attempt < maxAttempts) {
                window.requestAnimationFrame(() => initializeSalesFormWhenReady(attempt + 1));
            }
        }

        let salesModalObserverStarted = false;

        function observeSalesModalInitialization() {
            if (salesModalObserverStarted || !document.body) {
                return;
            }

            salesModalObserverStarted = true;

            const observer = new MutationObserver(() => {
                const modalRoot = getActiveSalesModalRoot();

                if (modalRoot?.querySelector('[data-sales-form-config]')) {
                    initializeSalesFormWhenReady();
                    applySalesPriceFallback(modalRoot);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }

        window.initializeBrokerSalesPage = function(scope = document) {
            if (typeof SalesQRScanner === 'function' && !window.salesQrScanner) {
                window.salesQrScanner = new SalesQRScanner();
            }

            const modalRoot = getActiveSalesModalRoot();
            const paymentAmountInput = modalRoot?.querySelector('#paid_amount');
            if (paymentAmountInput && typeof window.protectSalesAmountInput === 'function') {
                window.protectSalesAmountInput(paymentAmountInput);
            }

            const scanBtn = modalRoot?.querySelector('#scan-qr-btn');
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

            initializeSalesFormWhenReady();
            applySalesPriceFallback(modalRoot);
            observeSalesModalInitialization();
        };

        document.addEventListener('change', function(event) {
            if (!event.target.closest('.fish-type-select')) {
                return;
            }

            applySalesPriceFallback(getActiveSalesModalRoot());
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.initializeBrokerSalesPage(document);
        });

        function paymentForm(config = {}) {
            return {
                paidAmount: Number(config.initialPaidAmount ?? 0) > 0
                    ? Number(config.initialPaidAmount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '',
                maxPaymentAmount: Number(config.maxPaymentAmount ?? 0),
                paymentError: '',

                initializePaymentForm() {
                    if (this.parseMoney(this.paidAmount) > 0) {
                        this.validatePaymentAmount();
                    }
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

        function printReceiptBroker() {
            const receiptTitle = 'Receipt #{{ $printingSales ? $printingSales->id : "" }}';
            window.printReceipt('receipt-content', receiptTitle);
        }
    </script>
@endsection
