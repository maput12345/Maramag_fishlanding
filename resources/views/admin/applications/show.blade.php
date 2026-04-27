@extends('layouts.admin')

@php
    $requirementsVerified = $application->canBeQualified();
    $canConfirmWinner = !$application->broker && $application->application_status === 'Qualified' && $requirementsVerified;
    $winnerAlreadyConfirmed = (bool) $application->broker;
    $openingHasBiddingSchedule = (bool) ($application->applicationOpening?->bidding_date && $application->applicationOpening?->bidding_location);
    $reviewStallLabel = $application->selectedStall?->display_name
        ?? ($application->application_status === 'Winner'
            ? ($application->applicationOpening?->stall?->display_name ?? 'Awarded stall')
            : 'Open stall application');
    $reviewDraftPayload = is_array($reviewDraft?->draft_payload ?? null) ? $reviewDraft->draft_payload : [];
    $reviewDraftRequirements = collect($reviewDraftPayload['requirements'] ?? [])
        ->filter(fn ($draftRequirement) => is_array($draftRequirement))
        ->keyBy(fn ($draftRequirement) => (int) ($draftRequirement['id'] ?? 0));
    $statusBadgeClass = match ($application->application_status) {
        'Qualified', 'Winner' => 'app-status-badge--active',
        'Rejected', 'Not Selected' => 'app-status-badge--inactive',
        default => 'app-status-badge--neutral',
    };
@endphp

@section('content')
<div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <a href="{{ route('admin.applications.index') }}" class="app-back-link">&larr; Back to submitted applications</a>
        <div class="app-page-header mt-4">
            <div class="app-page-header__content">
                <p class="app-page-kicker">Application Review</p>
                <h1 class="app-page-title">{{ $application->name }}</h1>
                <p class="app-page-description">
                    {{ $reviewStallLabel }} - submitted {{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}
                </p>
            </div>
            <span class="app-status-badge {{ $statusBadgeClass }}">{{ $application->application_status }}</span>
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
            <p class="mt-2">Set the bidding start date and bidding location on the application opening before marking this applicant as qualified.</p>
        </section>
    @endunless

    @if($reviewDraft?->last_saved_at)
        <section class="rounded-3xl border border-sky-200 bg-sky-50 p-6 text-sm text-sky-900 shadow-sm">
            <p class="font-semibold">Autosaved draft restored.</p>
            <p class="mt-2">Your unfinished review remarks were restored from {{ $reviewDraft->last_saved_at->format('M d, Y h:i A') }}. Click <span class="font-semibold">Save Review</span> when the official review is ready.</p>
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
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bidding Start Date</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ optional($application->applicationOpening?->bidding_date)->format('M d, Y') ?? 'Not set' }}</dd>
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
                    <form action="{{ route('admin.applications.winner', $application) }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="selected_stall_id" class="block text-sm font-medium text-slate-700">Awarded Stall</label>
                            <select id="selected_stall_id" name="selected_stall_id" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
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
                @endphp
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-6" data-requirement-review-row>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $requirement->requirementType?->requirement_name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Uploaded {{ optional($requirement->uploaded_at)->format('M d, Y h:i A') ?? 'N/A' }}
                            </p>
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
                            <select name="requirements[{{ $loop->index }}][verification_status]" class="rounded-full border border-slate-300 px-4 py-2 text-sm">
                                @foreach(['Pending', 'Verified', 'Rejected'] as $verificationStatus)
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
                    <select id="application_status" name="application_status" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
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

    form.addEventListener('submit', () => {
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
