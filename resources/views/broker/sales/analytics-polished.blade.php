@php
$breadcrumbs = [
        ['title' => 'Sales & Analytics']
    ];

    $maxWeeklySales = max(1, $weeklySalesData->max('sales'));
    $periodLabel = \Carbon\Carbon::parse($dateFrom)->format('M d, Y')
        . ($dateFrom !== $dateTo ? ' - ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y') : '');
    $trendPeriodLabel = \Carbon\Carbon::parse($trendDateFrom ?? $dateFrom)->format('M d')
        . ' - '
        . \Carbon\Carbon::parse($trendDateTo ?? $dateTo)->format('M d, Y');
@endphp
@extends('layouts.broker')

@section('content')
<div class="w-full dashboard-shell">
    <section class="panel-card">
        <div class="panel-card__inner">
            <div class="panel-card__header">
                <div>
                    <h3 class="panel-card__title">Filters</h3>
                </div>
            </div>

            <form method="GET" action="{{ route('broker.sales.analytics') }}" x-data="{
                status: '{{ request('status') }}',
                dateFrom: '{{ request('date_from', $dateFrom) }}',
                dateTo: '{{ request('date_to', $dateTo) }}'
            }">
                <div class="analytics-filter-layout">
                    <div class="status-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" x-model="status" class="app-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="{{ \App\Constants\SalesStatusConstant::ACTIVE }}">Unpaid</option>
                            <option value="{{ \App\Constants\SalesStatusConstant::PAID }}">Paid</option>
                            <option value="{{ \App\Constants\SalesStatusConstant::PARTIALLY_PAID }}">Partially Paid</option>
                        </select>
                    </div>

                    <div class="fish-type-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" x-model="dateFrom" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="fish-type-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" x-model="dateTo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="buttons-field filter-action-group justify-end">
                        <a href="{{ route('broker.sales.analytics') }}" class="btn-clear">
                            Clear
                        </a>
                        <button type="submit" class="btn-search">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <div class="metric-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card metric-card--primary">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Transactions</p>
                    <p class="metric-card__value">{{ number_format($totalOrders) }}</p>
                    <p class="metric-card__meta">{{ $periodLabel }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-document-text />
                </span>
            </div>
        </div>

        <div class="metric-card metric-card--success">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Period Sales</p>
                    <p class="metric-card__value metric-card__value--money">₱{{ number_format($totalRevenue, 2) }}</p>
                    <p class="metric-card__meta">{{ $periodLabel }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-chart-pie />
                </span>
            </div>
        </div>

        <div class="metric-card metric-card--warning">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow" title="Outstanding Receivable Balance">Outstanding Balance</p>
                    <p class="metric-card__value metric-card__value--money">₱{{ number_format($totalBalance, 2) }}</p>
                    <p class="metric-card__meta">{{ $periodLabel }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-currency-dollar />
                </span>
            </div>
        </div>

        <div class="metric-card metric-card--neutral">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Fish Boxes Sold</p>
                    <p class="metric-card__value">{{ number_format($totalFishBoxes) }}</p>
                    <p class="metric-card__meta">{{ $periodLabel }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-cube />
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="panel-card chart-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Sales Trend</h3>
                        <p class="mt-1 text-sm text-gray-500">Rolling week: {{ $trendPeriodLabel }}</p>
                    </div>
                </div>

                <div class="chart-grid" style="--chart-columns: {{ max(1, count($weeklySalesData)) }};">
                    @foreach($weeklySalesData as $weekData)
                        <div class="chart-column">
                            <div class="chart-column__value">₱{{ number_format($weekData['sales'], 0) }}</div>
                            <div class="chart-column__track">
                                <div class="chart-column__bar" style="height: {{ ($weekData['sales'] / $maxWeeklySales) * 100 }}%;">
                                    <div class="chart-column__tooltip">₱{{ number_format($weekData['sales'], 2) }}</div>
                                </div>
                            </div>
                            <span class="chart-column__label">{{ $weekData['day'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Top Selling Commodities</h3>
                    </div>
                    <span class="panel-card__hint">{{ $periodLabel }}</span>
                </div>

                <div class="top-item-list">
                    @forelse($topItems as $index => $item)
                        <div class="top-item-row">
                            <div class="top-item-row__lead">
                                <div class="top-item-row__rank">{{ $index + 1 }}</div>
                                <div>
                                    <p class="top-item-row__title">{{ $item['name'] }}</p>
                                    <p class="top-item-row__meta tabular-nums">{{ $item['quantity'] }} sold</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format($item['revenue'], 2) }}</p>
                                @if($topItems->count() > 0)
                                    <div class="progress-track">
                                        <div class="progress-bar" style="width: {{ $topItems->first()['revenue'] > 0 ? ($item['revenue'] / $topItems->first()['revenue']) * 100 : 0 }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-cube class="heroicon" />
                            <p class="text-sm">No sales data available for this period.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <section class="panel-card">
        <div class="panel-card__inner">
            <div class="panel-card__header">
                <div>
                    <h3 class="panel-card__title">Sales Performance</h3>
                </div>
                <span class="panel-card__hint">Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} for {{ $periodLabel }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buyer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commodities</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($sale->sales_date)->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sale->formatted_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ $sale->buyer_name }}</div>
                                        <div class="text-gray-500 text-xs">{{ $sale->buyer_contact }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $sale->formatted_items }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium tabular-nums text-gray-900">₱{{ number_format($sale->total_amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium tabular-nums text-gray-900">₱{{ number_format($sale->paid_amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-status-badge :status="\App\Constants\SalesStatusConstant::getDisplayName($sale->status)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12">
                                    <div class="empty-state">
                                        <x-heroicon-o-document-text class="heroicon" />
                                        <p class="text-sm">No sales data available for the selected period and filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sales->hasPages())
                <div class="pt-4">
                    {{ $sales->appends(request()->query())->links('components.pagination') }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
