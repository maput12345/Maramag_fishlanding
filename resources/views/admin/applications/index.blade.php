@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    @if($errors->any())
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800 shadow-sm">
            <p class="font-semibold">Application setup could not be saved.</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="app-section-heading">
                <h2 class="app-section-title">Create Stall</h2>
            </div>
            <form action="{{ route('admin.applications.stalls.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="stall_number" class="block text-sm font-medium text-slate-700">Stall Number</label>
                    <input id="stall_number" name="stall_number" type="text" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                </div>
                <div>
                    <label for="stall_remarks" class="block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea id="stall_remarks" name="remarks" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"></textarea>
                </div>
                <button type="submit" class="app-button app-button--dark">Add Stall</button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="app-section-heading">
                <h2 class="app-section-title">Open Application Window</h2>
            </div>
            <form action="{{ route('admin.applications.openings.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="stall_id" class="block text-sm font-medium text-slate-700">Vacant Stall</label>
                    <select id="stall_id" name="stall_id" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <option value="">Select a stall</option>
                        @foreach($stalls as $stall)
                            <option value="{{ $stall->id }}" {{ (string) old('stall_id') === (string) $stall->id ? 'selected' : '' }}>
                                {{ $stall->display_name }} · {{ $stall->stall_status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-slate-700">Start Date</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-slate-700">End Date</label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="bidding_date" class="block text-sm font-medium text-slate-700">Bidding Start Date</label>
                        <input id="bidding_date" name="bidding_date" type="date" value="{{ old('bidding_date') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>
                    <div>
                        <label for="bidding_location" class="block text-sm font-medium text-slate-700">Bidding Location</label>
                        <input id="bidding_location" name="bidding_location" type="text" value="{{ old('bidding_location', 'Maramag Fish Landing') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" maxlength="255" required>
                    </div>
                </div>
                <button type="submit" class="app-button app-button--primary">Open Applications</button>
            </form>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Application Openings</h2>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Stall</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Window</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Bidding Schedule</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applications</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($openings as $opening)
                        <tr>
                            <td class="px-4 py-4">{{ $opening->stall?->display_name }}</td>
                            <td class="px-4 py-4">{{ optional($opening->start_date)->format('M d, Y') }} to {{ optional($opening->end_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ optional($opening->bidding_date)->format('M d, Y') ?? 'Not set' }}</div>
                                <div class="text-xs text-slate-500">{{ $opening->bidding_location ?: 'No location set' }}</div>
                            </td>
                            <td class="px-4 py-4">{{ $opening->opening_status }}</td>
                            <td class="px-4 py-4">{{ $opening->broker_applications_count }}</td>
                            <td class="px-4 py-4">
                                <form action="{{ route('admin.applications.openings.status', $opening) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="opening_status" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium">
                                        @foreach(['Open', 'Closed', 'Completed', 'Cancelled'] as $openingStatus)
                                            <option value="{{ $openingStatus }}" {{ $opening->opening_status === $openingStatus ? 'selected' : '' }}>{{ $openingStatus }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="app-button app-button--secondary">Save</button>
                                </form>

                                <details class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <summary class="cursor-pointer text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Edit Bidding Schedule</summary>
                                    <form action="{{ route('admin.applications.openings.update', $opening) }}" method="POST" class="mt-4 space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Start Date</label>
                                            <input
                                                name="bidding_date"
                                                type="date"
                                                value="{{ optional($opening->bidding_date)->format('Y-m-d') }}"
                                                class="mt-2 w-full rounded-2xl border border-slate-300 px-3 py-2 text-sm"
                                                required
                                            >
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No application openings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Submitted Applications</h2>
            </div>
            <form action="{{ route('admin.applications.index') }}" method="GET" class="flex items-center gap-2">
                <select name="status" class="rounded-full border border-slate-300 px-4 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach(['Submitted', 'Under Review', 'Needs Revision', 'Rejected', 'Qualified', 'Winner', 'Not Selected'] as $applicationStatus)
                        <option value="{{ $applicationStatus }}" {{ $status === $applicationStatus ? 'selected' : '' }}>{{ $applicationStatus }}</option>
                    @endforeach
                </select>
                <button type="submit" class="app-button app-button--secondary">Filter</button>
            </form>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applicant</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Stall</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Submitted</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($applications as $application)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $application->name }}</div>
                                <div class="text-xs text-slate-500">{{ $application->user?->email }}</div>
                            </td>
                            <td class="px-4 py-4">{{ $application->applicationOpening?->stall?->display_name }}</td>
                            <td class="px-4 py-4">{{ $application->application_status }}</td>
                            <td class="px-4 py-4">{{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.applications.show', $application) }}" class="app-button app-button--secondary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No applications found for the selected filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $applications->withQueryString()->links() }}
        </div>
    </section>
</div>
@endsection
