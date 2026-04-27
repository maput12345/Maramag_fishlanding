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
            <p class="font-semibold">Stall workspace changes could not be saved.</p>
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
            <form action="{{ route('admin.stalls.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="stall_number" class="block text-sm font-medium text-slate-700">Stall Number</label>
                    <input id="stall_number" name="stall_number" type="text" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                </div>
                <div>
                    <label for="stall_remarks" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="stall_remarks" name="remarks" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"></textarea>
                </div>
                <div>
                    <label for="stall_images" class="block text-sm font-medium text-slate-700">Stall Photos</label>
                    <input id="stall_images"
                           name="stall_images[]"
                           type="file"
                           accept=".jpg,.jpeg,.png,.webp"
                           multiple
                           class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <p class="mt-2 text-xs text-slate-500">Upload one or more clear photos of the stall exterior and surrounding area. Up to 6 images, 5MB each.</p>
                    @error('stall_images')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('stall_images.*')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="app-button app-button--dark">Add Stall</button>
            </form>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="app-section-heading">
                <h2 class="app-section-title">Declare Vacancy</h2>
            </div>
            <form action="{{ route('admin.stalls.openings.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="stall_id" class="block text-sm font-medium text-slate-700">Vacant Stall</label>
                    <select id="stall_id" name="stall_id" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <option value="">Select a stall</option>
                        @foreach($stalls as $stall)
                            <option value="{{ $stall->id }}" {{ (string) old('stall_id') === (string) $stall->id ? 'selected' : '' }}>
                                {{ $stall->display_name }} - {{ $stall->stall_status }}
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

                @php
                    $selectedRequirementIds = collect(old('requirement_type_ids', $requirementTypes->pluck('id')->all()))
                        ->map(fn ($requirementId) => (int) $requirementId)
                        ->all();
                    $requirementGroups = [
                        \App\Models\RequirementType::APPLICANT_TYPE_BOTH => 'All Applicants',
                        \App\Models\RequirementType::APPLICANT_TYPE_NATURAL => 'Natural Person',
                        \App\Models\RequirementType::APPLICANT_TYPE_JURIDICAL => 'Juridical Person',
                    ];
                    $selectedRequirementCount = $requirementTypes
                        ->filter(fn ($requirementType) => in_array($requirementType->id, $selectedRequirementIds, true))
                        ->count();
                @endphp

                <details
                    class="rounded-3xl border border-slate-200 bg-slate-50 p-5"
                    data-requirement-manager
                    @if($errors->has('requirement_type_ids')) open @endif
                >
                    <summary class="cursor-pointer list-none">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Application Requirements</h3>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600" data-selected-summary>
                                    {{ $selectedRequirementCount }} of {{ $requirementTypes->count() }} selected
                                </span>
                                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">Manage</span>
                            </div>
                        </div>
                    </summary>

                    <div class="mt-5 space-y-4">
                        <div class="grid gap-3 lg:grid-cols-[1fr,auto]">
                            <label class="block">
                                <span class="sr-only">Search requirements</span>
                                <input
                                    type="search"
                                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm"
                                    placeholder="Search requirement..."
                                    data-requirement-search
                                >
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700" data-requirement-action="default">
                                    Use Default Checklist
                                </button>
                                <button type="button" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700" data-requirement-action="all">
                                    Select All
                                </button>
                                <button type="button" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700" data-requirement-action="clear-optional">
                                    Clear Optional
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @foreach($requirementGroups as $audience => $groupLabel)
                                @php
                                    $groupedRequirements = $requirementTypes->where('audience', $audience);
                                @endphp

                                @if($groupedRequirements->isNotEmpty())
                                    <section class="rounded-2xl border border-slate-200 bg-white p-4" data-requirement-group>
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $groupLabel }}</h4>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ $groupedRequirements->count() }}</span>
                                        </div>
                                        <div class="grid gap-2">
                                            @foreach($groupedRequirements as $requirementType)
                                                <label
                                                    for="opening_requirement_{{ $requirementType->id }}"
                                                    class="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm"
                                                    data-requirement-item
                                                    data-search-text="{{ \Illuminate\Support\Str::lower($requirementType->requirement_name . ' ' . $groupLabel . ' ' . ($requirementType->description ?? '')) }}"
                                                >
                                                    <input
                                                        id="opening_requirement_{{ $requirementType->id }}"
                                                        name="requirement_type_ids[]"
                                                        type="checkbox"
                                                        value="{{ $requirementType->id }}"
                                                        class="mt-1 rounded border-slate-300 text-slate-900"
                                                        data-requirement-checkbox
                                                        data-default-required="{{ $requirementType->is_required ? 'true' : 'false' }}"
                                                        @checked(in_array($requirementType->id, $selectedRequirementIds, true))
                                                    >
                                                    <span>
                                                        <span class="font-semibold text-slate-900">{{ $requirementType->requirement_name }}</span>
                                                        <span class="mt-1 block text-xs text-slate-500">
                                                            {{ $requirementType->is_required ? 'Required' : 'Optional' }}
                                                        </span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </section>
                                @endif
                            @endforeach
                        </div>

                        <div class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800" data-requirement-empty>
                            No requirements match your search.
                        </div>
                    </div>
                </details>
                <button type="submit" class="app-button app-button--primary">Declare</button>
            </form>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Add Requirement</h2>
            </div>
            <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">{{ $requirementTypes->count() }} requirements</span>
        </div>

        <form action="{{ route('admin.stalls.requirements.store') }}" method="POST" class="mt-6 grid gap-4 lg:grid-cols-[1fr,0.8fr,0.7fr]">
            @csrf
            <div>
                <label for="requirement_name" class="block text-sm font-medium text-slate-700">Requirement Name</label>
                <input id="requirement_name" name="requirement_name" type="text" value="{{ old('requirement_name') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Example: Police Clearance" required>
            </div>
            <div>
                <label for="audience" class="block text-sm font-medium text-slate-700">Applies To</label>
                <select id="audience" name="audience" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    @foreach([
                        \App\Models\RequirementType::APPLICANT_TYPE_BOTH => 'All Applicants',
                        \App\Models\RequirementType::APPLICANT_TYPE_NATURAL => 'Natural Person',
                        \App\Models\RequirementType::APPLICANT_TYPE_JURIDICAL => 'Juridical Person',
                    ] as $audienceValue => $audienceLabel)
                        <option value="{{ $audienceValue }}" @selected(old('audience', \App\Models\RequirementType::APPLICANT_TYPE_BOTH) === $audienceValue)>{{ $audienceLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <label class="flex min-h-[3rem] flex-1 items-center gap-3 rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700">
                    <input name="is_required" type="checkbox" value="1" class="rounded border-slate-300 text-slate-900" @checked(old('is_required', '1'))>
                    Required by default
                </label>
                <button type="submit" class="app-button app-button--dark">Add</button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Stall Occupancy Overview</h2>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Stall</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Application Period</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Bidding Schedule</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applications</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($openings as $opening)
                        @php
                            $openingAvailabilityStatus = $opening->opening_status === 'Cancelled'
                                ? 'Cancelled'
                                : ($opening->stall?->stall_status === 'Occupied' ? 'Occupied' : 'Vacant');
                        @endphp
                        <tr>
                            <td class="px-4 py-4">{{ $opening->stall?->display_name }}</td>
                            <td class="px-4 py-4">{{ optional($opening->start_date)->format('M d, Y') }} to {{ optional($opening->end_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ optional($opening->bidding_date)->format('M d, Y') ?? 'Not set' }}</div>
                                <div class="text-xs text-slate-500">{{ $opening->bidding_location ?: 'No location set' }}</div>
                            </td>
                            <td class="px-4 py-4">{{ $openingAvailabilityStatus }}</td>
                            <td class="px-4 py-4">{{ $opening->broker_applications_count }}</td>
                            <td class="px-4 py-4">
                                <form action="{{ route('admin.stalls.openings.status', $opening) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="opening_status" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-requirement-manager]').forEach((manager) => {
        const searchInput = manager.querySelector('[data-requirement-search]');
        const checkboxes = Array.from(manager.querySelectorAll('[data-requirement-checkbox]'));
        const items = Array.from(manager.querySelectorAll('[data-requirement-item]'));
        const groups = Array.from(manager.querySelectorAll('[data-requirement-group]'));
        const summary = manager.querySelector('[data-selected-summary]');
        const emptyState = manager.querySelector('[data-requirement-empty]');

        const updateSummary = () => {
            const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

            if (summary) {
                summary.textContent = `${selectedCount} of ${checkboxes.length} selected`;
            }
        };

        const updateSearch = () => {
            const query = (searchInput?.value || '').trim().toLowerCase();
            let visibleItems = 0;

            items.forEach((item) => {
                const isVisible = !query || item.dataset.searchText.includes(query);

                item.classList.toggle('hidden', !isVisible);

                if (isVisible) {
                    visibleItems += 1;
                }
            });

            groups.forEach((group) => {
                const hasVisibleItem = Array.from(group.querySelectorAll('[data-requirement-item]'))
                    .some((item) => !item.classList.contains('hidden'));

                group.classList.toggle('hidden', !hasVisibleItem);
            });

            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleItems > 0);
            }
        };

        manager.querySelectorAll('[data-requirement-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.requirementAction;

                checkboxes.forEach((checkbox) => {
                    if (action === 'all') {
                        checkbox.checked = true;
                    }

                    if (action === 'default') {
                        checkbox.checked = checkbox.dataset.defaultRequired === 'true';
                    }

                    if (action === 'clear-optional' && checkbox.dataset.defaultRequired !== 'true') {
                        checkbox.checked = false;
                    }
                });

                updateSummary();
            });
        });

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSummary));
        searchInput?.addEventListener('input', updateSearch);
        updateSummary();
        updateSearch();
    });
});
</script>
@endsection
