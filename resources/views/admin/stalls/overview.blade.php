@extends('layouts.admin')

@section('content')
@php
    $overviewQuery = request()->except(['modal', 'opening', 'stall']);
    $visibleStalls = method_exists($stalls, 'getCollection') ? $stalls->getCollection() : collect($stalls);
    $editingScheduleOpening = null;
    $managingPhotosStall = null;

    if (request('modal') === 'edit-schedule') {
        $editingScheduleOpeningId = (int) request('opening');
        $editingScheduleOpening = $visibleStalls
            ->flatMap(fn ($stall) => $stall->applicationOpenings)
            ->first(fn ($opening) => (int) $opening->id === $editingScheduleOpeningId);
    }

    if (request('modal') === 'manage-photos') {
        $managingPhotosStallId = (int) request('stall');
        $managingPhotosStall = $visibleStalls->first(fn ($stall) => (int) $stall->id === $managingPhotosStallId);
    }
@endphp

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
                <h2 class="text-xl font-semibold text-slate-900">Stall Status</h2>
            </div>
        </div>

        <form action="{{ route('admin.stalls.overview') }}" method="GET" class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="flex-1">
                    <label for="stall_search" class="block text-sm font-semibold text-slate-900">Search Stall</label>
                    <div class="relative mt-2">
                        <input
                            id="stall_search"
                            name="stall_search"
                            type="search"
                            value="{{ $stallSearch }}"
                            placeholder="Search stall number, status, address, or remarks..."
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition-all duration-200 placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-200"
                        >
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                    <a href="{{ route('admin.stalls.overview') }}" class="btn-clear w-full sm:w-auto">Clear</a>
                    <button type="submit" class="btn-search w-full sm:w-auto">Search</button>
                </div>
            </div>
        </form>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-28 px-4 py-3 text-left font-semibold text-slate-600">Stall</th>
                        <th class="min-w-[14rem] px-4 py-3 text-left font-semibold text-slate-600">Location and Size</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="min-w-[11rem] px-4 py-3 text-left font-semibold text-slate-600">Application Period</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Bidding Info</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Requirements</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">Applicants</th>
                        <th class="min-w-[14rem] px-4 py-3 text-left font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($stalls as $stall)
                        @php
                            $opening = $stall->applicationOpenings->first();
                            $statusLabel = $stall->stall_status === 'Open for Application'
                                ? 'Open'
                                : $stall->stall_status;
                        @endphp
                        <tr class="align-top">
                            <td class="w-28 whitespace-nowrap px-4 py-4 font-semibold text-slate-950">{{ $stall->display_name }}</td>
                            <td class="max-w-xs px-4 py-4 text-slate-600">
                                <div class="font-medium text-slate-900">{{ $stall->address ?: 'No address recorded.' }}</div>
                                <div class="mt-1 text-xs tabular-nums text-slate-500">
                                    {{ $stall->length_meters ? number_format((float) $stall->length_meters, 2) : '0.00' }}m x
                                    {{ $stall->width_meters ? number_format((float) $stall->width_meters, 2) : '0.00' }}m =
                                    {{ $stall->area_sqm ? number_format((float) $stall->area_sqm, 2) : '0.00' }} sqm
                                </div>
                                @if($stall->remarks)
                                    <div class="mt-1 text-xs text-slate-500">{{ $stall->remarks }}</div>
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
                            <td class="px-4 py-4 text-right tabular-nums">{{ $opening?->requirement_types_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-right tabular-nums">{{ $opening?->broker_applications_count ?? 0 }}</td>
                            <td class="min-w-[14rem] px-4 py-4">
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

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ route('admin.stalls.overview', array_merge($overviewQuery, ['modal' => 'edit-schedule', 'opening' => $opening->id])) }}"
                                           class="app-button app-button--secondary px-3 py-2 text-xs">
                                            <x-heroicon-o-calendar-days class="h-4 w-4" />
                                            <span>Edit Schedule</span>
                                        </a>
                                        <a href="{{ route('admin.stalls.overview', array_merge($overviewQuery, ['modal' => 'manage-photos', 'stall' => $stall->id])) }}"
                                           class="app-button app-button--secondary px-3 py-2 text-xs">
                                            <x-heroicon-o-photo class="h-4 w-4" />
                                            <span>Manage Photos</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-medium text-slate-500">No opening actions</span>
                                        <a href="{{ route('admin.stalls.overview', array_merge($overviewQuery, ['modal' => 'manage-photos', 'stall' => $stall->id])) }}"
                                           class="app-button app-button--secondary px-3 py-2 text-xs">
                                            <x-heroicon-o-photo class="h-4 w-4" />
                                            <span>Manage Photos</span>
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                                {{ $stallSearch ? 'No stalls matched your search.' : 'No stalls created yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $stalls->links('components.pagination') }}
        </div>
    </section>
</div>

@if($editingScheduleOpening)
    <x-app-modal
        title="Edit Bidding Schedule"
        subtitle="{{ $editingScheduleOpening->stall?->display_name ?? 'Selected stall' }}"
        :close-url="route('admin.stalls.overview', $overviewQuery)"
        max-width="lg"
        body-class="workspace-popup__body--soft"
    >
        <x-slot:icon>
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                <x-heroicon-o-calendar-days class="h-6 w-6" />
            </span>
        </x-slot:icon>

        <form action="{{ route('admin.stalls.openings.update', $editingScheduleOpening) }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="modal_bidding_date" class="block text-sm font-semibold text-slate-800">Bidding Date</label>
                    <input
                        id="modal_bidding_date"
                        name="bidding_date"
                        type="date"
                        value="{{ old('bidding_date', optional($editingScheduleOpening->bidding_date)->format('Y-m-d')) }}"
                        class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4 text-sm"
                        required
                    >
                </div>
                <div>
                    <label for="modal_bidding_time" class="block text-sm font-semibold text-slate-800">Bidding Time</label>
                    <input
                        id="modal_bidding_time"
                        name="bidding_time"
                        type="time"
                        value="{{ old('bidding_time', optional($editingScheduleOpening->bidding_time)->format('H:i')) }}"
                        class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4 text-sm"
                        required
                    >
                </div>
            </div>

            <div>
                <label for="modal_bidding_location" class="block text-sm font-semibold text-slate-800">Bidding Location</label>
                <input
                    id="modal_bidding_location"
                    name="bidding_location"
                    type="text"
                    value="{{ old('bidding_location', $editingScheduleOpening->bidding_location) }}"
                    class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4 text-sm"
                    maxlength="255"
                    required
                >
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="app-button app-button--secondary" @click="close()">Cancel</button>
                <button type="submit" class="app-button app-button--primary">Save Schedule</button>
            </div>
        </form>
    </x-app-modal>
@endif

@if($managingPhotosStall)
    <x-app-modal
        title="Manage Photos"
        subtitle="{{ $managingPhotosStall->display_name }}"
        :close-url="route('admin.stalls.overview', $overviewQuery)"
        max-width="2xl"
        body-class="workspace-popup__body--soft"
    >
        <x-slot:icon>
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                <x-heroicon-o-photo class="h-6 w-6" />
            </span>
        </x-slot:icon>

        <div class="space-y-5">
            @if($managingPhotosStall->stallImages->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach($managingPhotosStall->stallImages as $stallImage)
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <a href="{{ asset('storage/' . $stallImage->image_path) }}" target="_blank" rel="noopener">
                                <img
                                    src="{{ asset('storage/' . $stallImage->image_path) }}"
                                    alt="{{ $managingPhotosStall->display_name }} photo {{ $loop->iteration }}"
                                    class="h-40 w-full object-cover"
                                >
                            </a>
                            <form
                                action="{{ route('admin.stalls.photos.destroy', [$managingPhotosStall, $stallImage]) }}"
                                method="POST"
                                data-swal="delete"
                                data-record-name="{{ $managingPhotosStall->display_name }} photo {{ $loop->iteration }}"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-50">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                    <span>Remove Photo</span>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                    No photos uploaded yet.
                </div>
            @endif

            <form action="{{ route('admin.stalls.photos.store', $managingPhotosStall) }}" method="POST" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
                @csrf
                <div>
                    <label for="modal_stall_images" class="block text-sm font-semibold text-slate-800">Add Photos</label>
                    <input
                        id="modal_stall_images"
                        name="stall_images[]"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                    >
                    <p class="mt-2 text-xs text-slate-500">Upload JPG, PNG, or WebP. Maximum 6 photos per stall.</p>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="app-button app-button--secondary" @click="close()">Close</button>
                    <button type="submit" class="app-button app-button--primary">Upload Photos</button>
                </div>
            </form>
        </div>
    </x-app-modal>
@endif
@endsection
