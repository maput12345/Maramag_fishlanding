@php
    $breadcrumbs = [
        ['title' => 'Sales & Analytics']
    ];
    $periodLabel = \Carbon\Carbon::parse($dateFrom)->format('M d, Y')
        . ($dateFrom !== $dateTo ? ' - ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y') : '');
    $trendPeriodLabel = \Carbon\Carbon::parse($trendDateFrom ?? $dateFrom)->format('M d')
        . ' - '
        . \Carbon\Carbon::parse($trendDateTo ?? $dateTo)->format('M d, Y');
@endphp

@extends('layouts.broker')

@section('content')
            <div class="w-full content-spacing">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Sales Analytics</h1>
                            <p class="text-gray-600 mt-2">Review sales activity, collections, and top-performing commodities for {{ $periodLabel }}.</p>
                        </div>
                    </div>
                </div>

                <!-- Sales Filters -->
                <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
                    <form method="GET" action="{{ route('broker.sales.analytics') }}" x-data="{
                        status: '{{ request('status') }}',
                        dateFrom: '{{ request('date_from', $dateFrom) }}',
                        dateTo: '{{ request('date_to', $dateTo) }}'
                    }">
                        <div class="analytics-filter-layout">
                            <!-- Status Filter -->
                            <div class="status-field">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" x-model="status" class="app-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Status</option>
                                    <option value="{{ \App\Constants\SalesStatusConstant::ACTIVE }}">Unpaid</option>
                                    <option value="{{ \App\Constants\SalesStatusConstant::PAID }}">Paid</option>
                                    <option value="{{ \App\Constants\SalesStatusConstant::PARTIALLY_PAID }}">Partially Paid</option>
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
                                <a href="{{ route('broker.sales.analytics') }}"
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

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Orders -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Total Orders</p>
                                <p class="text-3xl font-bold">{{ number_format($totalOrders) }}</p>
                                <p class="text-green-100 text-sm mt-1">Orders in period</p>
                            </div>
                            <div class="w-12 h-12 bg-green-400 rounded-lg flex items-center justify-center">
                                <x-heroicon-o-document-text class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Daily Sales -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Period Sales</p>
                                <p class="text-3xl font-bold">₱{{ number_format($totalRevenue, 2) }}</p>
                                <p class="text-blue-100 text-sm mt-1">Revenue in period</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-400 rounded-lg flex items-center justify-center">
                                <x-heroicon-o-chart-pie class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Payment to Collect -->
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Outstanding Balance</p>
                                <p class="text-3xl font-bold">₱{{ number_format($totalBalance, 2) }}</p>
                                <p class="text-purple-100 text-sm mt-1">Outstanding Balance</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-400 rounded-lg flex items-center justify-center">
                                <x-heroicon-o-currency-dollar class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Fish Boxes -->
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm font-medium">Total Fish Boxes</p>
                                <p class="text-3xl font-bold">{{ number_format($totalFishBoxes) }}</p>
                                <p class="text-orange-100 text-sm mt-1">Commodities sold</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-400 rounded-lg flex items-center justify-center">
                                <x-heroicon-o-cube class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Analytics Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Sales Trend Chart -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend</h3>
                        <p class="-mt-2 mb-4 text-sm text-gray-500">Rolling week: {{ $trendPeriodLabel }}</p>

                        <!-- Sales Amount Labels - Outside/Above Chart Container -->
                        <div class="flex justify-between space-x-2 mb-4">
                            @foreach($weeklySalesData as $weekData)
                                <div class="flex-1 text-center">
                                    <span class="text-xs font-medium text-gray-700">
                                        ₱{{ number_format($weekData['sales'], 0) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Chart Bars -->
                        <div class="h-64 flex items-end justify-between space-x-2">
                            @php
                                $maxSales = $weeklySalesData->max('sales');
                            @endphp
                            @foreach($weeklySalesData as $weekData)
                                <div class="flex flex-col items-center flex-1">
                                    <!-- Chart Bar -->
                                    <div class="w-full bg-green-200 rounded-t"
                                         style="height: {{ $maxSales > 0 ? ($weekData['sales'] / $maxSales) * 240 : 0 }}px">
                                        @if($weekData['sales'] > 0)
                                            <div class="w-full bg-green-600 rounded-t h-full">
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Day Label -->
                                    <span class="text-xs text-gray-500 mt-3">{{ $weekData['day'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Top Selling Items -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Top Selling Commodities</h3>
                            <div class="text-sm text-gray-500">Period: {{ $periodLabel }}</div>
                        </div>
                        <div class="space-y-4">
                            @forelse($topItems as $index => $item)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">{{ $index + 1 }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $item['name'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">{{ $item['quantity'] }} sold</p>
                                        @if($topItems->count() > 0)
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $topItems->count() > 0 ? ($item['revenue'] / $topItems->first()['revenue']) * 100 : 0 }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8">
                                    <p class="text-gray-500">No sales data available for this period</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sales Performance Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Sales Performance</h3>
                            <div class="text-sm text-gray-500">
                                Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} results for {{ $periodLabel }}
                            </div>
                        </div>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($sale->sales_date)->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium tabular-nums text-gray-900">
                                            {{ $sale->formatted_id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>
                                                <div class="font-medium">{{ $sale->buyer_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $sale->buyer_contact }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $sale->formatted_items }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium tabular-nums text-gray-900">
                                            ₱{{ number_format($sale->total_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            ₱{{ number_format($sale->paid_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <x-status-badge :status="\App\Constants\SalesStatusConstant::getDisplayName($sale->status)" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <p class="text-lg font-medium">No sales found</p>
                                                <p class="text-sm">No sales data available for the selected period and filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $sales->appends(request()->query())->links('components.pagination') }}
                    </div>
                </div>
            </div>
@endsection
