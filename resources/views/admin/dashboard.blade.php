@php
    $breadcrumbs = [
        ['title' => 'Dashboard']
    ];
@endphp

@extends('layouts.admin')

@section('content')
<div class="w-full dashboard-shell">
    <div class="dashboard-header">
        <span class="dashboard-kicker">LEEO Command Center</span>
        <div>
            <h1 class="dashboard-title">LEEO Dashboard</h1>
        </div>
    </div>

    <div class="metric-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.users.index', ['tab' => 'brokers']) }}" class="metric-card metric-card--primary">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Active Brokers</p>
                    <p class="metric-card__value">{{ number_format($totalBrokers) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-users />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.sales.index') }}" class="metric-card metric-card--success">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Fish Boxes Sold</p>
                    <p class="metric-card__value">{{ number_format($totalFishBoxesSold) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-archive-box />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.sales.tracking') }}" class="metric-card metric-card--warning">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Current Missing Boxes</p>
                    <p class="metric-card__value">{{ number_format($totalFishBoxesMissing) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-exclamation-triangle />
                </span>
            </div>
        </a>

        <a href="{{ route('admin.sales.tracking') }}" class="metric-card metric-card--neutral">
            <div class="metric-card__row">
                <div>
                    <p class="metric-card__eyebrow">Currently Returned Boxes</p>
                    <p class="metric-card__value">{{ number_format($totalFishBoxesReturned) }}</p>
                </div>
                <span class="metric-card__icon">
                    <x-heroicon-o-arrow-uturn-left />
                </span>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Top Fish Names Sold</h3>
                    </div>
                </div>

                <div class="activity-list">
                    @forelse($topFishTypes as $fishType)
                        <div class="activity-row">
                            <div class="activity-row__icon">
                                <x-heroicon-o-archive-box class="w-4 h-4" />
                            </div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $fishType['fish_type']->name }}</p>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">{{ $fishType['sold_count'] }} sold</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-archive-box class="heroicon" />
                            <p class="text-sm">No fish names sold yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Top Brokers This Month</h3>
                    </div>
                    <a href="{{ route('admin.sales.index') }}" class="panel-card__action">View All</a>
                </div>

                <div class="activity-list">
                    @forelse($topBrokers as $brokerData)
                        <div class="activity-row">
                            <div class="activity-row__icon">
                                <x-heroicon-o-users class="w-4 h-4" />
                            </div>
                            <div class="activity-row__body">
                                <p class="activity-row__title">{{ $brokerData['broker']->name ?? 'Unknown Broker' }}</p>
                                <p class="activity-row__detail">{{ $brokerData['sales_count'] }} sales this month</p>
                            </div>
                            <div class="activity-row__value">
                                <p class="activity-row__amount">{{ $brokerData['fishbox_count'] }} boxes</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-heroicon-o-users class="heroicon" />
                            <p class="text-sm">No broker sales recorded this month.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <section class="panel-card mt-6">
        <div class="panel-card__inner">
            <div class="panel-card__header">
                <div>
                    <h3 class="panel-card__title">Current Missing Boxes</h3>
                    <p class="panel-card__hint">See which broker currently owns each missing fish box.</p>
                </div>
                <a href="{{ route('admin.sales.tracking', ['action' => 'Missing']) }}" class="panel-card__action">Open Tracking</a>
            </div>

            <div class="activity-list">
                @forelse($currentMissingBoxes as $missingBox)
                    <div class="activity-row">
                        <div class="activity-row__icon">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </div>
                        <div class="activity-row__body">
                            <p class="activity-row__title">{{ $missingBox->name }}</p>
                            <p class="activity-row__detail">
                                {{ $missingBox->fish_type_name ?? 'Unknown Fish Name' }}
                                • {{ $missingBox->broker?->name ?? 'Unknown Broker' }}
                                @if($missingBox->broker?->stall_name)
                                    • {{ $missingBox->broker->stall_name }}
                                @endif
                            </p>
                        </div>
                        <div class="activity-row__value">
                            <p class="activity-row__amount text-red-600">Missing</p>
                            <p class="activity-row__detail">{{ $missingBox->updated_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <x-heroicon-o-shield-check class="heroicon" />
                        <p class="text-sm">No boxes are currently marked missing.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
