@extends('layouts.broker')

@php
    $breadcrumbs = [
        ['title' => 'Fish Box Tracking']
    ];
@endphp

@section('content')
<div class="w-full">

    <div class="mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Currently Missing</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($missingCount) }}</p>
                    <p class="text-xs text-red-600">Live box status only</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('broker.fish-boxes.tracking') }}" x-data="{ search: @js((string) request('search', '')) }">
            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">Search</label>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="lg:flex-1">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               x-model="search"
                               placeholder="Search fish box, fish, QR code, or buyer..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                    </div>

                    <div class="filter-action-group justify-start lg:flex-none lg:justify-end">
                        <a href="{{ route('broker.fish-boxes.tracking') }}"
                           class="btn-clear">
                            Clear
                        </a>
                        <button type="submit"
                                class="btn-search">
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="mb-4 text-sm text-gray-600">
        Showing {{ $trackingFishBoxes->firstItem() ?? 0 }} to {{ $trackingFishBoxes->lastItem() ?? 0 }} of {{ $trackingFishBoxes->total() }} missing fish boxes
        @if(request()->filled('search'))
            <span class="text-blue-600">(filtered)</span>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-3 md:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                        <th class="px-3 py-3 md:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish Box</th>
                        <th class="px-3 py-3 md:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fish</th>
                        <th class="px-3 py-3 md:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Buyer</th>
                        <th class="px-3 py-3 md:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                        <th class="px-3 py-3 md:px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($trackingFishBoxes as $fishBox)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-4 md:px-6 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                <span class="hidden md:inline">{{ $fishBox->updated_at->format('M d, Y H:i') }}</span>
                                <span class="md:hidden">{{ $fishBox->updated_at->format('M d, H:i') }}</span>
                            </td>
                            <td class="px-3 py-4 md:px-6 whitespace-nowrap">
                                <div class="text-xs md:text-sm font-medium text-gray-900">{{ $fishBox->name }}</div>
                                <div class="text-xs text-gray-500">ID: {{ $fishBox->id }}</div>
                            </td>
                            <td class="px-3 py-4 md:px-6 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                {{ $fishBox->fish_type_name ?? 'N/A' }}
                            </td>
                            <td class="px-3 py-4 md:px-6 whitespace-nowrap text-xs md:text-sm text-gray-900">
                                {{ $fishBox->last_buyer_name ?: 'No buyer recorded' }}
                            </td>
                            <td class="px-3 py-4 md:px-6 whitespace-nowrap text-xs md:text-sm text-gray-500 font-mono">
                                {{ \Illuminate\Support\Str::limit($fishBox->qr_code ?? 'N/A', 18) }}
                            </td>
                            <td class="px-3 py-4 md:px-6 whitespace-nowrap">
                                <x-status-badge :status="$fishBox->status" size="sm" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <x-heroicon-o-archive-box class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                <p class="text-lg font-medium text-gray-900 mb-2">No missing fish boxes found</p>
                                <p class="text-sm">You do not have any current missing fish boxes that match this search.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-6 py-4">
            {{ $trackingFishBoxes->appends(request()->query())->links('components.pagination') }}
        </div>
    </div>
</div>
@endsection
