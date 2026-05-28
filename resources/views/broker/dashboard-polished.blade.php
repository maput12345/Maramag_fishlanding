@php
$breadcrumbs = [
        ['title' => 'Dashboard']
    ];

    $maxDailySales = max(1, $dailySalesData->max('sales'));
    $thisWeekTotal = $dailySalesData->sum('sales');
    $lastWeekTotal = ($previousWeekSalesData ?? collect())->sum('sales');
    $growthPercent = $lastWeekTotal > 0 ? (($thisWeekTotal - $lastWeekTotal) / $lastWeekTotal) * 100 : 0;
    $maxWeeklySales = max(1, $weeklySalesData->max('sales'));
@endphp
@extends('layouts.broker')

@section('content')
<div class="w-full dashboard-shell">
    <div class="metric-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('broker.sales.sales') }}" class="metric-card metric-card--primary">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Sales Count Today</p>
                    <p class="metric-card__value">{{ number_format($ordersToday) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-document-text />
                </span>
            </div>
        </a>

        <a href="{{ route('broker.sales.sales') }}" class="metric-card metric-card--success">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Daily Collection</p>
                    <p class="metric-card__value metric-card__value--money">₱{{ number_format($salesToday, 2) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-chart-pie />
                </span>
            </div>
        </a>

        <a href="{{ route('broker.sales.sales') }}" class="metric-card metric-card--warning">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Outstanding Balance</p>
                    <p class="metric-card__value metric-card__value--money">₱{{ number_format($salesBalance, 2) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-currency-dollar />
                </span>
            </div>
        </a>

        <a href="{{ route('broker.inventory.index') }}" class="metric-card metric-card--neutral">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Total Sold Boxes</p>
                    <p class="metric-card__value">{{ number_format($totalFishBoxes) }}</p>
                    <p class="metric-card__meta">Boxes sold today</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-cube />
                </span>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">My Recent Sales</h3>
                    </div>
                    <a href="{{ route('broker.sales.sales') }}" class="panel-card__action">View All</a>
                </div>

                <div class="activity-list">
                    @forelse ($recentSales as $index => $sale)
                        <div class="activity-row">
                            <div class="activity-row__icon">{{ $index + 1 }}</div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $sale->buyer_name }} ({{ $sale->formatted_items }})</p>
                                <div class="activity-row__detail">
                                    @if($sale->status === \App\Constants\SalesStatusConstant::PAID)
                                        <x-status-badge :status="$sale->status" label="Paid" />
                                    @elseif($sale->status === \App\Constants\SalesStatusConstant::PARTIALLY_PAID)
                                        <x-status-badge :status="$sale->status" label="Partially Paid" />
                                        <span class="ml-2">Balance: ₱{{ number_format($sale->total_amount - $sale->paid_amount, 2) }}</span>
                                    @else
                                        <x-status-badge :status="$sale->status" label="Unpaid" />
                                        <span class="ml-2">Balance: ₱{{ number_format($sale->total_amount, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">₱{{ number_format($sale->paid_amount, 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-document-text class="heroicon" />
                            <p class="text-sm">No recent sales yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card chart-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Daily Sales Performance</h3>
                    </div>
                </div>

                <div class="chart-grid" style="--chart-columns: {{ max(1, count($dailySalesData)) }};">
                    @foreach($dailySalesData as $dayData)
                        <div class="chart-column">
                            <div class="chart-column__value">₱{{ number_format($dayData['sales'], 0) }}</div>
                            <div class="chart-column__track">
                                <div class="chart-column__bar" style="height: {{ ($dayData['sales'] / $maxDailySales) * 100 }}%;">
                                    <div class="chart-column__tooltip">₱{{ number_format($dayData['sales'], 2) }}</div>
                                </div>
                            </div>
                            <span class="chart-column__label">{{ $dayData['day'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="chart-summary-grid">
                    <div class="chart-summary-box">
                        <p class="chart-summary-box__value">₱{{ number_format($thisWeekTotal, 0) }}</p>
                        <p class="chart-summary-box__label">This Week</p>
                    </div>
                    <div class="chart-summary-box">
                        <p class="chart-summary-box__value {{ $growthPercent >= 0 ? 'chart-summary-box__value--positive' : 'chart-summary-box__value--warning' }}">
                            {{ $growthPercent >= 0 ? '+' : '' }}{{ number_format($growthPercent, 1) }}%
                        </p>
                        <p class="chart-summary-box__label">Growth</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Top Selling Commodities</h3>
                    </div>
                    <span class="panel-card__hint">All Time</span>
                </div>

                <div class="top-item-list">
                    @forelse($topItems as $index => $item)
                        <div class="top-item-row">
                            <div class="top-item-row__lead">
                                <div class="top-item-row__rank">{{ $index + 1 }}</div>
                                <div>
                                    <p class="top-item-row__title">{{ $item['name'] }}</p>
                                    <p class="top-item-row__meta">{{ $item['quantity'] }} sold</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">₱{{ number_format($item['revenue'], 2) }}</p>
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
                            <p class="text-sm">No sales data available.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card chart-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">This Month's Weekly Sales Trend</h3>
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
    </div>
</div>
@endsection
