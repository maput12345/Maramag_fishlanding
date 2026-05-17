@php
    $breadcrumbs = [
        ['title' => 'Dashboard']
    ];
@endphp

@extends('layouts.admin')

@section('content')
<div class="w-full min-w-0 dashboard-shell">

    <div class="dashboard-metric-grid">
        <a href="{{ route('admin.sales.index') }}" class="metric-card metric-card--success h-full">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Total Sold Boxes</p>
                    <p class="metric-card__value">{{ number_format($totalFishBoxesSold) }}</p>
                    <p class="metric-card__meta">Boxes sold today</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-shopping-bag />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.stalls.index') }}" class="metric-card metric-card--neutral h-full">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Occupied Stalls</p>
                    <p class="metric-card__value">{{ number_format($occupiedStallsCount) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-home-modern />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.stalls.index') }}" class="metric-card metric-card--primary h-full">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Vacant Stalls</p>
                    <p class="metric-card__value">{{ number_format($vacantStallsCount) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-building-storefront />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.applications.index', ['status' => 'Submitted']) }}" class="metric-card metric-card--warning h-full">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Needs Review</p>
                    <p class="metric-card__value">{{ number_format($needsReviewCount) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-clipboard-document-check />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.applications.index') }}" class="metric-card metric-card--neutral h-full">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Total Applications</p>
                    <p class="metric-card__value">{{ number_format($totalApplicationsCount) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-document-text />
                </span>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="panel-card" x-data="{ period: 'daily' }">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Top Brokers</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                            <button type="button"
                                class="rounded-md px-3 py-1 text-xs font-semibold transition"
                                :class="period === 'daily' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                @click="period = 'daily'">
                                Daily
                            </button>
                            <button type="button"
                                class="rounded-md px-3 py-1 text-xs font-semibold transition"
                                :class="period === 'weekly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                @click="period = 'weekly'">
                                Weekly
                            </button>
                        </div>
                    </div>
                </div>

                <div class="activity-list" x-show="period === 'daily'">
                    @forelse($topBrokersDaily as $index => $brokerData)
                        <div class="activity-row">
                            <div class="activity-row__icon">{{ $index + 1 }}</div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $brokerData['broker']->name ?? 'Unknown Broker' }}</p>
                                <p class="activity-row__detail">Sales today</p>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">{{ number_format($brokerData['sales_count']) }} sales</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-users class="heroicon" />
                            <p class="text-sm">No broker sales today.</p>
                        </div>
                    @endforelse
                </div>

                <div class="activity-list" x-show="period === 'weekly'" x-cloak>
                    @forelse($topBrokersWeekly as $index => $brokerData)
                        <div class="activity-row">
                            <div class="activity-row__icon">{{ $index + 1 }}</div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $brokerData['broker']->name ?? 'Unknown Broker' }}</p>
                                <p class="activity-row__detail">Sales in the last 7 days</p>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">{{ number_format($brokerData['sales_count']) }} sales</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-users class="heroicon" />
                            <p class="text-sm">No broker sales this week.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card" x-data="{ period: 'daily' }">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Top Fish Sold</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                            <button type="button"
                                class="rounded-md px-3 py-1 text-xs font-semibold transition"
                                :class="period === 'daily' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                @click="period = 'daily'">
                                Daily
                            </button>
                            <button type="button"
                                class="rounded-md px-3 py-1 text-xs font-semibold transition"
                                :class="period === 'weekly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                @click="period = 'weekly'">
                                Weekly
                            </button>
                        </div>
                    </div>
                </div>

                <div class="activity-list" x-show="period === 'daily'">
                    @forelse($topFishDaily as $index => $fishData)
                        <div class="activity-row">
                            <div class="activity-row__icon">{{ $index + 1 }}</div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $fishData['fish_type']->name ?? 'Unknown Fish' }}</p>
                                <p class="activity-row__detail">Sold today</p>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">{{ number_format($fishData['sold_count']) }} boxes</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-archive-box class="heroicon" />
                            <p class="text-sm">No fish sold today.</p>
                        </div>
                    @endforelse
                </div>

                <div class="activity-list" x-show="period === 'weekly'" x-cloak>
                    @forelse($topFishWeekly as $index => $fishData)
                        <div class="activity-row">
                            <div class="activity-row__icon">{{ $index + 1 }}</div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $fishData['fish_type']->name ?? 'Unknown Fish' }}</p>
                                <p class="activity-row__detail">Sold in the last 7 days</p>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">{{ number_format($fishData['sold_count']) }} boxes</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-archive-box class="heroicon" />
                            <p class="text-sm">No fish sold this week.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
