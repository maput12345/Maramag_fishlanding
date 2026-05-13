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
            <form action="{{ route('admin.stalls.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4" data-stall-confirm="create-stall">
                @csrf
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="mt-2 text-lg font-semibold text-slate-950">Suggested stall number: Stall {{ $nextStallNumber }}</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3" data-stall-area-calculator>
                    <div>
                        <label for="length_meters" class="block text-sm font-medium text-slate-700">Length (meters)</label>
                        <input id="length_meters" name="length_meters" type="number" step="0.01" min="0.01" value="{{ old('length_meters') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required data-stall-length>
                        @error('length_meters')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="width_meters" class="block text-sm font-medium text-slate-700">Width (meters)</label>
                        <input id="width_meters" name="width_meters" type="number" step="0.01" min="0.01" value="{{ old('width_meters') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required data-stall-width>
                        @error('width_meters')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="area_sqm_preview" class="block text-sm font-medium text-slate-700">Area (sqm)</label>
                        <input id="area_sqm_preview" type="text" value="0.00" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" readonly data-stall-area>
                    </div>
                </div>
                <div>
                    <label for="stall_address" class="block text-sm font-medium text-slate-700">Location</label>
                    <input id="stall_address" name="address" type="text" value="{{ old('address') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Enter stall location or market area" required>
                    @error('address')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="stall_remarks" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="stall_remarks" name="remarks" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Add notes about the stall layout, landmarks, or condition">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
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
                <h2 class="app-section-title">Stall Vacancy and Bidding Schedule</h2>
            </div>
            <form action="{{ route('admin.stalls.openings.store') }}" method="POST" class="mt-6 space-y-4" data-stall-confirm="open-vacancy">
                @csrf
                <div>
                    @php
                        $selectedStallIds = collect(old('stall_ids', []))
                            ->map(fn ($stallId) => (int) $stallId)
                            ->all();
                    @endphp
                    <div class="flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium text-slate-700">Vacant Stalls</label>
                        <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white" data-stall-selected-summary>
                            {{ count($selectedStallIds) }} {{ count($selectedStallIds) === 1 ? 'stall' : 'stalls' }} selected
                        </span>
                    </div>
                    <div class="mt-2 rounded-3xl border border-slate-200 bg-slate-50 p-4" data-stall-multi-select>
                        @if($vacantStalls->isEmpty())
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                                No vacant stalls available
                            </div>
                        @else
                            <div class="grid gap-3 sm:grid-cols-[1fr,auto]">
                                <div>
                                    <label for="vacant_stall_picker" class="sr-only">Select vacant stall</label>
                                    <select id="vacant_stall_picker" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm" data-stall-picker>
                                        <option value="">Select a vacant stall...</option>
                                        @foreach($vacantStalls as $stall)
                                            <option value="{{ $stall->id }}" data-stall-name="{{ $stall->display_name }}">
                                                {{ $stall->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">Selected Stalls</p>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2" data-stall-selected-list></div>

                                <p class="mt-3 text-sm text-slate-500" data-stall-selected-empty>
                                    No stalls selected yet.
                                </p>
                            </div>

                            <div data-stall-hidden-inputs>
                                @foreach($selectedStallIds as $selectedStallId)
                                    <input type="hidden" name="stall_ids[]" value="{{ $selectedStallId }}" data-stall-hidden-input>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @error('stall_ids')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('stall_ids.*')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
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
                        <label for="bidding_date" class="block text-sm font-medium text-slate-700">Bidding Date</label>
                        <input id="bidding_date" name="bidding_date" type="date" value="{{ old('bidding_date') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>
                    <div>
                        <label for="bidding_time" class="block text-sm font-medium text-slate-700">Bidding Time</label>
                        <input id="bidding_time" name="bidding_time" type="time" value="{{ old('bidding_time') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>
                </div>
                <div>
                    <label for="bidding_location" class="block text-sm font-medium text-slate-700">Bidding Location</label>
                    <input id="bidding_location" name="bidding_location" type="text" value="{{ old('bidding_location', 'Maramag Fish Landing') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" maxlength="255" required>
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
                <button type="submit" class="app-button app-button--primary">Open</button>
            </form>
        </div>
    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-stall-area-calculator]').forEach((calculator) => {
        const lengthInput = calculator.querySelector('[data-stall-length]');
        const widthInput = calculator.querySelector('[data-stall-width]');
        const areaOutput = calculator.querySelector('[data-stall-area]');

        const updateArea = () => {
            const length = parseFloat(lengthInput?.value || '0');
            const width = parseFloat(widthInput?.value || '0');
            const area = length > 0 && width > 0 ? length * width : 0;

            if (areaOutput) {
                areaOutput.value = area.toFixed(2);
            }
        };

        lengthInput?.addEventListener('input', updateArea);
        widthInput?.addEventListener('input', updateArea);
        updateArea();
    });

    document.querySelectorAll('[data-stall-multi-select]').forEach((manager) => {
        const picker = manager.querySelector('[data-stall-picker]');
        const addButton = manager.querySelector('[data-stall-add]');
        const selectedList = manager.querySelector('[data-stall-selected-list]');
        const emptyState = manager.querySelector('[data-stall-selected-empty]');
        const hiddenInputs = manager.querySelector('[data-stall-hidden-inputs]');
        const summary = document.querySelector('[data-stall-selected-summary]');
        const stallNames = new Map(
            Array.from(picker?.querySelectorAll('option[value]') || [])
                .filter((option) => option.value !== '')
                .map((option) => [option.value, option.dataset.stallName || option.textContent.trim()])
        );

        const updateSummary = () => {
            const selectedCount = hiddenInputs?.querySelectorAll('[data-stall-hidden-input]').length || 0;

            if (summary) {
                summary.textContent = `${selectedCount} ${selectedCount === 1 ? 'stall' : 'stalls'} selected`;
            }

            if (emptyState) {
                emptyState.classList.toggle('hidden', selectedCount > 0);
            }
        };

        const refreshPickerOptions = () => {
            const selectedIds = new Set(
                Array.from(hiddenInputs?.querySelectorAll('[data-stall-hidden-input]') || [])
                    .map((input) => input.value)
            );

            Array.from(picker?.querySelectorAll('option[value]') || []).forEach((option) => {
                if (option.value === '') {
                    return;
                }

                option.hidden = selectedIds.has(option.value);
                option.disabled = selectedIds.has(option.value);
            });

            if (picker && selectedIds.has(picker.value)) {
                picker.value = '';
            }
        };

        const renderSelectedStalls = () => {
            if (!selectedList || !hiddenInputs) {
                return;
            }

            selectedList.innerHTML = '';

            Array.from(hiddenInputs.querySelectorAll('[data-stall-hidden-input]')).forEach((input) => {
                const pill = document.createElement('span');
                pill.className = 'inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white';
                pill.dataset.selectedStallPill = input.value;

                const label = document.createElement('span');
                label.textContent = stallNames.get(input.value) || `Stall ${input.value}`;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'rounded-full bg-white/15 px-1.5 text-white transition hover:bg-white/25';
                removeButton.setAttribute('aria-label', `Remove ${label.textContent}`);
                removeButton.textContent = 'x';
                removeButton.addEventListener('click', () => {
                    input.remove();
                    renderSelectedStalls();
                    refreshPickerOptions();
                    updateSummary();
                });

                pill.append(label, removeButton);
                selectedList.appendChild(pill);
            });
        };

        const addSelectedStall = () => {
            if (!picker?.value || !hiddenInputs) {
                return;
            }

            const alreadySelected = hiddenInputs.querySelector(`[data-stall-hidden-input][value="${CSS.escape(picker.value)}"]`);

            if (alreadySelected) {
                picker.value = '';
                return;
            }

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'stall_ids[]';
            input.value = picker.value;
            input.dataset.stallHiddenInput = 'true';
            hiddenInputs.appendChild(input);

            picker.value = '';
            renderSelectedStalls();
            refreshPickerOptions();
            updateSummary();
        };

        addButton?.addEventListener('click', addSelectedStall);
        picker?.addEventListener('change', addSelectedStall);
        renderSelectedStalls();
        refreshPickerOptions();
        updateSummary();
    });

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

    document.querySelectorAll('form[data-stall-confirm]').forEach((form) => {
        let confirmed = false;

        form.addEventListener('submit', (event) => {
            if (confirmed) {
                return;
            }

            event.preventDefault();

            const action = form.dataset.stallConfirm;
            const checkedStalls = form.querySelectorAll('[data-stall-hidden-input]').length;
            const checkedRequirements = form.querySelectorAll('[data-requirement-checkbox]:checked').length;
            const config = action === 'open-vacancy'
                ? {
                    title: 'Open stall vacancy?',
                    text: `This will open ${checkedStalls} ${checkedStalls === 1 ? 'stall' : 'stalls'} for applications with ${checkedRequirements} selected ${checkedRequirements === 1 ? 'requirement' : 'requirements'}.`,
                    confirmButtonText: 'Yes, open vacancy',
                    confirmButtonColor: '#059669',
                    icon: 'question',
                }
                : {
                    title: 'Create this stall?',
                    text: 'Please confirm the stall dimensions, address, description, and uploaded photos before saving.',
                    confirmButtonText: 'Yes, create stall',
                    confirmButtonColor: '#0f172a',
                    icon: 'question',
                };

            if (!window.Swal) {
                if (window.confirm(config.title)) {
                    confirmed = true;
                    form.requestSubmit();
                }

                return;
            }

            window.Swal.fire({
                title: config.title,
                text: config.text,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonText: config.confirmButtonText,
                cancelButtonText: 'Review again',
                confirmButtonColor: config.confirmButtonColor,
                cancelButtonColor: '#64748b',
                focusCancel: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmed = true;
                    form.requestSubmit();
                }
            });
        });
    });
});
</script>
@endsection
