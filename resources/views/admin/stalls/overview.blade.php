@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    @if(session('success') || session('error') || session('info'))
        <section class="space-y-3">
            @if(session('success'))
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800 shadow-sm">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800 shadow-sm">{{ session('error') }}</div>
            @endif

            @if(session('info'))
                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-800 shadow-sm">{{ session('info') }}</div>
            @endif
        </section>
    @endif

    @if($errors->any())
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800 shadow-sm">
            <p class="font-semibold">Stall overview changes could not be saved.</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Stall Overview</h2>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Stall</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Location and Size</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Photos</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Application Period</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Bidding Info</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Requirements</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applicants</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($stalls as $stall)
                        @php
                            $opening = $stall->applicationOpenings->first();
                            $galleryImages = $stall->gallery_image_urls;
                            $statusLabel = $stall->stall_status === 'Open for Application'
                                ? 'Open'
                                : $stall->stall_status;
                        @endphp
                        <tr>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ $stall->display_name }}</td>
                            <td class="max-w-xs px-4 py-4 text-slate-600">
                                <div class="font-medium text-slate-900">{{ $stall->address ?: 'No address recorded.' }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $stall->length_meters ? number_format((float) $stall->length_meters, 2) : '0.00' }}m x
                                    {{ $stall->width_meters ? number_format((float) $stall->width_meters, 2) : '0.00' }}m =
                                    {{ $stall->area_sqm ? number_format((float) $stall->area_sqm, 2) : '0.00' }} sqm
                                </div>
                                @if($stall->remarks)
                                    <div class="mt-1 text-xs text-slate-500">{{ $stall->remarks }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if(count($galleryImages) > 0)
                                    <div class="flex items-center gap-2">
                                        @foreach(array_slice($galleryImages, 0, 3) as $galleryImage)
                                            <a href="{{ $galleryImage }}" target="_blank" rel="noopener" class="block h-12 w-12 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                                <img src="{{ $galleryImage }}" alt="{{ $stall->display_name }} photo {{ $loop->iteration }}" class="h-full w-full object-cover">
                                            </a>
                                        @endforeach
                                        @if(count($galleryImages) > 3)
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">+{{ count($galleryImages) - 3 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">No photos</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <x-status-badge :status="$statusLabel" />
                            </td>
                            <td class="px-4 py-4">
                                @if($opening)
                                    {{ optional($opening->start_date)->format('M d, Y') }} to {{ optional($opening->end_date)->format('M d, Y') }}
                                @else
                                    <span class="text-xs text-slate-500">No opening yet</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($opening)
                                    <div class="font-medium text-slate-900">{{ optional($opening->bidding_date)->format('M d, Y') ?? 'Not set' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($opening->bidding_time)->format('h:i A') ?? 'No time set' }}</div>
                                    <div class="text-xs text-slate-500">{{ $opening->bidding_location ?: 'No location set' }}</div>
                                @else
                                    <span class="text-xs text-slate-500">No bidding schedule</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">{{ $opening?->requirement_types_count ?? 0 }}</td>
                            <td class="px-4 py-4">{{ $opening?->broker_applications_count ?? 0 }}</td>
                            <td class="px-4 py-4">
                                @if($opening)
                                    @php
                                        $openingAvailabilityStatus = $opening->opening_status === 'Cancelled'
                                            ? 'Cancelled'
                                            : ($stall->stall_status === 'Occupied' ? 'Occupied' : 'Vacant');
                                    @endphp
                                    <form action="{{ route('admin.stalls.openings.status', $opening) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="opening_status" class="app-select rounded-full border border-slate-300 px-3 py-2 text-xs font-medium">
                                            @foreach(['Vacant', 'Occupied', 'Cancelled'] as $openingStatus)
                                                <option value="{{ $openingStatus }}" {{ $openingAvailabilityStatus === $openingStatus ? 'selected' : '' }}>{{ $openingStatus }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="app-button app-button--secondary">Save</button>
                                    </form>

                                    <details class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <summary class="cursor-pointer text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Edit Bidding Schedule</summary>
                                        <form action="{{ route('admin.stalls.openings.update', $opening) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Date</label>
                                                    <input
                                                        name="bidding_date"
                                                        type="date"
                                                        value="{{ optional($opening->bidding_date)->format('Y-m-d') }}"
                                                        class="mt-2 w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm"
                                                        required
                                                    >
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Time</label>
                                                    <input
                                                        name="bidding_time"
                                                        type="time"
                                                        value="{{ optional($opening->bidding_time)->format('H:i') }}"
                                                        class="mt-2 w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm"
                                                        required
                                                    >
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Location</label>
                                                <input
                                                    name="bidding_location"
                                                    type="text"
                                                    value="{{ $opening->bidding_location }}"
                                                    class="mt-2 w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm"
                                                    maxlength="255"
                                                    required
                                                >
                                            </div>
                                            <button type="submit" class="app-button app-button--secondary">Save Schedule</button>
                                        </form>
                                    </details>
                                @else
                                    <span class="text-xs font-medium text-slate-500">No actions available</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-slate-500">No stalls created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
