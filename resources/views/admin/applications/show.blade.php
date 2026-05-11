@extends('layouts.admin')

@php
    $requirementsVerified = $application->canBeQualified();
    $canConfirmWinner = !$application->broker && $application->application_status === 'Qualified' && $requirementsVerified;
    $winnerAlreadyConfirmed = (bool) $application->broker;
    $openingHasBiddingSchedule = (bool) ($application->applicationOpening?->bidding_date && $application->applicationOpening?->bidding_time && $application->applicationOpening?->bidding_location);
    $reviewStallLabel = $application->selectedStall?->display_name
        ?? ($application->application_status === 'Winner'
            ? ($application->applicationOpening?->stall?->display_name ?? 'Awarded stall')
            : 'Open stall application');
    $reviewDraftPayload = is_array($reviewDraft?->draft_payload ?? null) ? $reviewDraft->draft_payload : [];
    $reviewDraftRequirements = collect($reviewDraftPayload['requirements'] ?? [])
        ->filter(fn ($draftRequirement) => is_array($draftRequirement))
        ->keyBy(fn ($draftRequirement) => (int) ($draftRequirement['id'] ?? 0));
    $hasPendingRevisionReview = $application->hasPendingRevisionReview();
    $submissionTimelineLabel = $hasPendingRevisionReview ? 'resubmitted' : 'submitted';
    $submissionTimelineDate = $hasPendingRevisionReview ? $application->revision_resubmitted_at : $application->submitted_at;
@endphp

@section('content')
<div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <a href="{{ route('admin.applications.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-800 transition-all duration-200 hover:bg-slate-50 hover:shadow-sm">
            <span aria-hidden="true">&larr;</span>
            <span>Back to Submitted Applications</span>
        </a>
        <div class="app-page-header mt-4">
            <div class="app-page-header__content">
                <p class="app-page-kicker">Application Review</p>
                <h1 class="app-page-title">{{ $application->name }}</h1>
                <p class="app-page-description">
                    {{ $reviewStallLabel }} -
                    {{ $submissionTimelineLabel }}
                    {{ optional($submissionTimelineDate)->format('M d, Y h:i A') ?? 'N/A' }}
                </p>
            </div>
            <x-status-badge :status="$application->application_status" />
        </div>
    </section>

    @if($errors->any())
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800 shadow-sm">
            <p class="font-semibold">Review could not be saved.</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @unless($openingHasBiddingSchedule)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 shadow-sm">
            <p class="font-semibold">Bidding schedule is incomplete.</p>
            <p class="mt-2">Set the bidding date, time, and location on the application opening before marking this applicant as qualified.</p>
        </section>
    @endunless

    @if($hasPendingRevisionReview)
        <section class="rounded-3xl border border-orange-200 bg-orange-50 p-6 text-sm text-orange-900 shadow-sm">
            <div class="flex items-start gap-3">
                <x-heroicon-o-arrow-path class="mt-0.5 h-5 w-5 flex-shrink-0 text-orange-600" />
                <div>
                    <p class="font-semibold">Revision Resubmitted</p>
                    <p class="mt-2">
                        The applicant submitted updated revision items on
                        {{ optional($application->revision_resubmitted_at)->format('M d, Y h:i A') }}.
                        This is revision attempt #{{ $application->revision_count }}.
                    </p>
                </div>
            </div>
        </section>
    @endif

    <section class="grid gap-6 lg:grid-cols-[1.25fr,0.95fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="app-section-heading">
                <h2 class="app-section-title">Applicant Information</h2>
            </div>
            <dl class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Full Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Email</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->user?->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Business Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->business_name ?: 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Contact Number</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->contact_number }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Address</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Reviewed By</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->reviewedBy?->name ?? 'Pending' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Selected By</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->selectedBy?->name ?? 'Pending' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Date</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ optional($application->applicationOpening?->bidding_date)->format('M d, Y') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Time</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ optional($application->applicationOpening?->bidding_time)->format('h:i A') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Location</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->applicationOpening?->bidding_location ?: 'Not set' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="app-section-heading">
                <h2 class="app-section-title">Winner Selection</h2>
            </div>
            @if($winnerAlreadyConfirmed)
                <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">
                    <p class="font-semibold">Broker profile already created.</p>
                    <p class="mt-1">Assigned to {{ $application->broker->stall?->display_name ?? 'the selected stall' }}.</p>
                </div>
                <div class="mt-6">
                    <button type="button" disabled class="app-button app-button--confirmed" aria-disabled="true">
                        Winner Already Confirmed
                    </button>
                </div>
            @elseif($canConfirmWinner)
                <p class="mt-4 text-sm text-slate-600">Once the offline bidding decision is final, record the winner here and the system will activate the broker account automatically.</p>
                @if($availableWinnerStalls->isNotEmpty())
                    <form action="{{ route('admin.applications.winner', $application) }}" method="POST" class="mt-6 space-y-4" data-swal="winner" data-record-name="{{ $application->name }}">
                        @csrf
                        <div>
                            <label for="selected_stall_id" class="block text-sm font-medium text-slate-700">Awarded Stall</label>
                            <select id="selected_stall_id" name="selected_stall_id" class="app-select mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                <option value="">Select the stall to award</option>
                                @foreach($availableWinnerStalls as $stall)
                                    <option value="{{ $stall->id }}" {{ (string) old('selected_stall_id', $application->selected_stall_id) === (string) $stall->id ? 'selected' : '' }}>
                                        {{ $stall->display_name }} - {{ $stall->stall_status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="app-button app-button--success">
                            Confirm Winner and Activate Broker
                        </button>
                    </form>
                @else
                    <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                        No vacant stall is available for assignment right now.
                    </div>
                    <div class="mt-6">
                        <button type="button" disabled class="app-button app-button--muted" aria-disabled="true">
                            Confirm Winner and Activate Broker
                        </button>
                    </div>
                @endif
            @elseif($application->application_status === 'Qualified')
                <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    This application is marked <span class="font-semibold">Qualified</span>, but some requirement reviews are still pending or rejected. Verify every requirement before selecting a winner.
                </div>
                <div class="mt-6">
                    <button type="button" disabled class="app-button app-button--muted" aria-disabled="true">
                        Confirm Winner and Activate Broker
                    </button>
                </div>
            @else
                <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                    This application must be marked <span class="font-semibold text-slate-900">Qualified</span> before it can be selected as the winner.
                </div>
                <div class="mt-6">
                    <button type="button" disabled class="app-button app-button--muted" aria-disabled="true">
                        Confirm Winner and Activate Broker
                    </button>
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="app-section-heading">
            <h2 class="app-section-title">Requirement Verification</h2>
        </div>
        <form
            action="{{ route('admin.applications.review', $application) }}"
            method="POST"
            class="mt-6 space-y-6"
            data-review-autosave-form
            data-autosave-url="{{ route('admin.applications.review-draft', $application) }}"
        >
            @csrf
            @method('PATCH')

            @foreach($application->requirements as $requirement)
                @php
                    $draftRequirementReview = $reviewDraftRequirements->get($requirement->id, []);
                    $isNewRevisionFile = $hasPendingRevisionReview
                        && $application->revision_resubmitted_at
                        && $requirement->uploaded_at
                        && $requirement->uploaded_at->gte($application->revision_resubmitted_at);
                @endphp
                <article class="rounded-3xl border {{ $isNewRevisionFile ? 'border-orange-300 bg-orange-50/70 ring-2 ring-orange-100' : 'border-slate-200 bg-slate-50' }} p-6" data-requirement-review-row>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-slate-900">{{ $requirement->requirementType?->requirement_name }}</h3>
                                @if($isNewRevisionFile)
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-orange-700 ring-1 ring-orange-200">
                                        New revision file
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-600">
                                Uploaded {{ optional($requirement->uploaded_at)->format('M d, Y h:i A') ?? 'N/A' }}
                            </p>
                            @if($isNewRevisionFile)
                                <p class="mt-2 text-sm font-medium text-orange-700">
                                    Applicant replaced this file during revision attempt #{{ $application->revision_count }}.
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            @if($requirement->file_url)
                                <a href="{{ $requirement->file_url }}" target="_blank" rel="noopener" class="app-button app-button--secondary">
                                    View File
                                </a>
                            @endif
                            @php
                                $selectedVerificationStatus = old(
                                    'requirements.' . $loop->index . '.verification_status',
                                    $draftRequirementReview['verification_status'] ?? $requirement->verification_status
                                );
                            @endphp
                            <input type="hidden" name="requirements[{{ $loop->index }}][id]" value="{{ $requirement->id }}">
                            <select name="requirements[{{ $loop->index }}][verification_status]"
                                    class="app-select requirement-status-select rounded-full border border-slate-300 px-4 py-2 text-sm"
                                    data-requirement-status-select>
                                @foreach(['Pending', 'Verified', 'Needs Revision', 'Rejected'] as $verificationStatus)
                                    <option value="{{ $verificationStatus }}" {{ $selectedVerificationStatus === $verificationStatus ? 'selected' : '' }}>
                                        {{ $verificationStatus }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700">Requirement Remarks</label>
                        <textarea name="requirements[{{ $loop->index }}][remarks]" rows="2" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('requirements.' . $loop->index . '.remarks', $draftRequirementReview['remarks'] ?? $requirement->remarks) }}</textarea>
                    </div>
                </article>
            @endforeach

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="application_status" class="block text-sm font-medium text-slate-700">Overall Application Status</label>
                    <select id="application_status" name="application_status" class="app-select mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        @foreach(['Under Review', 'Needs Revision', 'Rejected', 'Qualified'] as $reviewStatus)
                            <option value="{{ $reviewStatus }}" {{ old('application_status', $reviewDraftPayload['application_status'] ?? $application->application_status) === $reviewStatus ? 'selected' : '' }}>
                                {{ $reviewStatus }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="remarks" class="block text-sm font-medium text-slate-700">LEEO Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('remarks', $reviewDraftPayload['remarks'] ?? $application->remarks) }}</textarea>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" data-review-autosave-status>
                @if($reviewDraft?->last_saved_at)
                    Draft restored. Last saved {{ $reviewDraft->last_saved_at->format('M d, Y h:i A') }}.
                @else
                    Autosave is ready. Requirement remarks and statuses will be saved as a draft while you review.
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" class="app-button app-button--primary">
                    Save Review
                </button>
            </div>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-review-autosave-form]');

    if (!form) {
        return;
    }

    const statusEl = document.querySelector('[data-review-autosave-status]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const autosaveUrl = form.dataset.autosaveUrl;
    let autosaveTimer = null;
    let activeRequest = null;
    let isSubmittingOfficialReview = false;
    let isOfficialReviewConfirmed = false;
    const verificationStatusSelects = form.querySelectorAll('[data-requirement-status-select]');
    const verificationStatusClasses = [
        'requirement-status-select--pending',
        'requirement-status-select--needs-revision',
        'requirement-status-select--verified',
        'requirement-status-select--rejected',
    ];

    const updateVerificationStatusColor = (select) => {
        select.classList.remove(...verificationStatusClasses);

        const statusStyles = {
            Pending: {
                className: 'requirement-status-select--pending',
                borderColor: '#fde68a',
                backgroundColor: '#fffbeb',
                color: '#92400e',
            },
            'Needs Revision': {
                className: 'requirement-status-select--needs-revision',
                borderColor: '#fdba74',
                backgroundColor: '#fff7ed',
                color: '#c2410c',
            },
            Verified: {
                className: 'requirement-status-select--verified',
                borderColor: '#86efac',
                backgroundColor: '#f0fdf4',
                color: '#166534',
            },
            Rejected: {
                className: 'requirement-status-select--rejected',
                borderColor: '#fecaca',
                backgroundColor: '#fef2f2',
                color: '#991b1b',
            },
        };
        const statusStyle = statusStyles[select.value] || statusStyles.Pending;

        select.classList.add(statusStyle.className);
        select.style.borderColor = statusStyle.borderColor;
        select.style.backgroundColor = statusStyle.backgroundColor;
        select.style.color = statusStyle.color;
    };

    verificationStatusSelects.forEach((select) => {
        updateVerificationStatusColor(select);
        select.addEventListener('change', () => updateVerificationStatusColor(select));
    });

    const setStatus = (message) => {
        if (statusEl) {
            statusEl.textContent = message;
        }
    };

    const collectPayload = () => {
        const requirementPayloads = Array.from(form.querySelectorAll('[data-requirement-review-row]')).map((row) => {
            return {
                id: row.querySelector('input[name$="[id]"]')?.value || '',
                verification_status: row.querySelector('select[name$="[verification_status]"]')?.value || 'Pending',
                remarks: row.querySelector('textarea[name$="[remarks]"]')?.value || '',
            };
        });

        return {
            application_status: form.querySelector('[name="application_status"]')?.value || '',
            remarks: form.querySelector('[name="remarks"]')?.value || '',
            requirements: requirementPayloads,
        };
    };

    const saveDraft = async () => {
        if (isSubmittingOfficialReview || !autosaveUrl) {
            return;
        }

        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = new AbortController();
        setStatus('Saving draft...');

        try {
            const response = await fetch(autosaveUrl, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                keepalive: true,
                body: JSON.stringify(collectPayload()),
                signal: activeRequest.signal,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Draft could not be saved.');
            }

            setStatus(data.last_saved_at_label ? `Draft saved at ${data.last_saved_at_label}.` : 'Draft saved.');
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            setStatus('Draft not saved. Please keep this page open and check your connection.');
        } finally {
            activeRequest = null;
        }
    };

    const scheduleDraftSave = () => {
        if (isSubmittingOfficialReview) {
            return;
        }

        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(saveDraft, 1200);
    };

    form.addEventListener('input', (event) => {
        if (event.target.matches('textarea')) {
            scheduleDraftSave();
        }
    });

    form.addEventListener('change', (event) => {
        if (event.target.matches('textarea, select')) {
            scheduleDraftSave();
        }
    });

    form.addEventListener('focusout', (event) => {
        if (event.target.matches('textarea, select')) {
            window.clearTimeout(autosaveTimer);
            saveDraft();
        }
    });

    const reviewConfirmationConfig = (status) => {
        const requirementStatuses = Array.from(verificationStatusSelects)
            .map((select) => select.value)
            .reduce((counts, statusValue) => {
                counts[statusValue] = (counts[statusValue] || 0) + 1;
                return counts;
            }, {});

        const requirementSummary = Object.entries(requirementStatuses)
            .map(([statusValue, count]) => `${count} ${statusValue}`)
            .join(', ');

        const baseText = requirementSummary
            ? `Requirement statuses: ${requirementSummary}.`
            : 'Please review the requirement statuses before continuing.';

        const configs = {
            'Needs Revision': {
                title: 'Send this back for revision?',
                text: `${baseText} The applicant will need to correct and resubmit the marked items.`,
                confirmButtonText: 'Yes, request revision',
                confirmButtonColor: '#ea580c',
                icon: 'warning',
            },
            Qualified: {
                title: 'Mark this application as qualified?',
                text: `${baseText} The applicant will move forward to the bidding stage.`,
                confirmButtonText: 'Yes, qualify applicant',
                confirmButtonColor: '#059669',
                icon: 'question',
            },
            Rejected: {
                title: 'Reject this application?',
                text: `${baseText} This will mark the application as rejected.`,
                confirmButtonText: 'Yes, reject application',
                confirmButtonColor: '#dc2626',
                icon: 'warning',
            },
            'Under Review': {
                title: 'Save as under review?',
                text: `${baseText} This will save the official review status as Under Review.`,
                confirmButtonText: 'Yes, save review',
                confirmButtonColor: '#2563eb',
                icon: 'question',
            },
        };

        return configs[status] || configs['Under Review'];
    };

    form.addEventListener('submit', (event) => {
        if (!isOfficialReviewConfirmed) {
            event.preventDefault();

            const selectedStatus = form.querySelector('[name="application_status"]')?.value || 'Under Review';
            const config = reviewConfirmationConfig(selectedStatus);

            if (!window.Swal) {
                if (window.confirm(config.title)) {
                    isOfficialReviewConfirmed = true;
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
                    isOfficialReviewConfirmed = true;
                    form.requestSubmit();
                }
            });

            return;
        }

        isSubmittingOfficialReview = true;
        window.clearTimeout(autosaveTimer);

        if (activeRequest) {
            activeRequest.abort();
        }

        setStatus('Saving official review...');
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            window.clearTimeout(autosaveTimer);
            saveDraft();
        }
    });
});
</script>
@endsection
