@extends('layouts.admin')

@section('content')
<style>
    .submitted-applications-filter {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        align-items: end;
    }

    .submitted-applications-filter__control {
        height: 3rem;
        width: 100%;
        border-radius: 1rem;
        border: 1px solid rgb(203 213 225);
        background: #ffffff;
        padding: 0 1rem;
        font-size: 0.875rem;
    }

    .submitted-applications-filter__actions {
        display: grid;
        grid-template-columns: repeat(2, auto);
        gap: 0.75rem;
        justify-content: end;
        align-items: end;
    }

    .submitted-applications-filter__button {
        height: 3rem;
        justify-content: center;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .submitted-applications-filter {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .submitted-applications-filter__actions {
            grid-column: 1 / -1;
        }
    }

    @media (min-width: 1280px) {
        .submitted-applications-filter {
            grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        }

        .submitted-applications-filter__actions {
            grid-column: auto;
        }
    }

    .submitted-applications-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .submitted-applications-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 9999px;
        border: 1px solid rgb(203 213 225);
        background: #ffffff;
        padding: 0.7rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: rgb(71 85 105);
        transition: all 0.2s ease;
    }

    .submitted-applications-tab:hover {
        border-color: rgb(15 23 42);
        color: rgb(15 23 42);
    }

    .submitted-applications-tab--active {
        border-color: rgb(15 23 42);
        background: rgb(15 23 42);
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.16);
    }

    .submitted-applications-tab__count {
        display: inline-flex;
        min-width: 1.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        background: rgb(241 245 249);
        padding: 0.15rem 0.45rem;
        font-size: 0.75rem;
        color: rgb(71 85 105);
    }

    .submitted-applications-tab--active .submitted-applications-tab__count {
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
    }

    .submitted-applications-summary {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    @media (min-width: 768px) {
        .submitted-applications-summary {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .submitted-applications-summary-card {
        border-radius: 1.25rem;
        border: 1px solid rgb(226 232 240);
        background: #ffffff;
        padding: 1rem;
    }

    .submitted-applications-summary-card__label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .submitted-applications-summary-card__value {
        margin-top: 0.35rem;
        font-size: 1.75rem;
        font-weight: 800;
        color: rgb(15 23 42);
    }

    .submission-type-badge {
        display: inline-flex;
        align-items: center;
        width: max-content;
        border-radius: 9999px;
        padding: 0.35rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .submission-type-badge--new {
        border: 1px solid rgb(191 219 254);
        background: rgb(239 246 255);
        color: rgb(30 64 175);
    }

    .submission-type-badge--resubmitted {
        border: 1px solid rgb(253 186 116);
        background: rgb(255 247 237);
        color: rgb(194 65 12);
    }
</style>

<div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Submitted Applications</h2>
            </div>
            <div class="submitted-applications-summary">
                <div class="submitted-applications-summary-card">
                    <p class="submitted-applications-summary-card__label">New Applications</p>
                    <p class="submitted-applications-summary-card__value">{{ $submissionSummary['new'] ?? 0 }}</p>
                </div>
                <div class="submitted-applications-summary-card">
                    <p class="submitted-applications-summary-card__label">Revision Resubmissions</p>
                    <p class="submitted-applications-summary-card__value">{{ $submissionSummary['resubmitted'] ?? 0 }}</p>
                </div>
                <div class="submitted-applications-summary-card">
                    <p class="submitted-applications-summary-card__label">Needs Review</p>
                    <p class="submitted-applications-summary-card__value">{{ $submissionSummary['needs_review'] ?? 0 }}</p>
                </div>
                <div class="submitted-applications-summary-card">
                    <p class="submitted-applications-summary-card__label">Qualified</p>
                    <p class="submitted-applications-summary-card__value">{{ $submissionSummary['qualified'] ?? 0 }}</p>
                </div>
            </div>
            <nav class="submitted-applications-tabs" aria-label="Application status groups">
                @foreach($applicationStatusTabs as $tabKey => $tab)
                    <a
                        href="{{ route('admin.applications.index', array_merge(request()->except(['page', 'tab']), ['tab' => $tabKey])) }}"
                        class="submitted-applications-tab {{ $activeApplicationTab === $tabKey ? 'submitted-applications-tab--active' : '' }}"
                        aria-current="{{ $activeApplicationTab === $tabKey ? 'page' : 'false' }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        <span class="submitted-applications-tab__count">{{ $applicationTabCounts[$tabKey] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
            <form action="{{ route('admin.applications.index') }}" method="GET" class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                <input type="hidden" name="tab" value="{{ $activeApplicationTab }}">
                <div class="submitted-applications-filter">
                    <div class="min-w-0">
                        <label for="opening_batch_id" class="block text-sm font-medium text-slate-700">Opening Batch</label>
                        <select id="opening_batch_id" name="opening_batch_id" class="submitted-applications-filter__control mt-2">
                            <option value="">All batches</option>
                            @foreach($openingBatches as $openingBatch)
                                <option value="{{ $openingBatch->id }}" @selected((int) $openingBatchId === $openingBatch->id)>{{ $openingBatch->display_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="stall_id" class="block text-sm font-medium text-slate-700">Stall</label>
                        <select id="stall_id" name="stall_id" class="submitted-applications-filter__control mt-2">
                            <option value="">All stalls</option>
                            @foreach($stalls as $stall)
                                <option value="{{ $stall->id }}" @selected((int) $stallId === $stall->id)>{{ $stall->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="application_date" class="block text-sm font-medium text-slate-700">Application Date</label>
                        <input id="application_date" name="application_date" type="date" value="{{ $applicationDate }}" class="submitted-applications-filter__control mt-2">
                    </div>
                    <div class="min-w-0">
                        <label for="submission_type" class="block text-sm font-medium text-slate-700">Submission Type</label>
                        <select id="submission_type" name="submission_type" class="submitted-applications-filter__control mt-2">
                            <option value="">All submissions</option>
                            <option value="new" @selected($submissionType === 'new')>New applications</option>
                            <option value="resubmitted" @selected($submissionType === 'resubmitted')>Revision resubmissions</option>
                        </select>
                    </div>
                    <div class="min-w-0">
                        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="status" name="status" class="submitted-applications-filter__control mt-2">
                            <option value="">All statuses</option>
                            @foreach(['Pending', 'For Review', 'Submitted', 'Under Review', 'Needs Revision', 'Qualified', 'Winner', 'Approved', 'Rejected', 'Not Selected'] as $applicationStatus)
                                <option value="{{ $applicationStatus }}" @selected($status === $applicationStatus)>{{ $applicationStatus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="submitted-applications-filter__actions filter-action-group">
                        <a href="{{ route('admin.applications.index') }}" class="btn-clear submitted-applications-filter__button">Clear</a>
                        <button type="submit" class="btn-search submitted-applications-filter__button">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applicant</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Opening Batch</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Stalls</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Submission</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Submitted</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($applications as $application)
                        @php
                            $batch = $application->openingBatch ?? $application->applicationOpening?->openingBatch;
                            $batchStalls = $batch
                                ? $batch->applicationOpenings->pluck('stall.display_name')->filter()->join(', ')
                                : null;
                            $applicationStall = $application->applicationOpening?->stall?->display_name;
                            $isResubmitted = $application->hasPendingRevisionReview();
                        @endphp
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $application->name }}</div>
                                <div class="text-xs text-slate-500">{{ $application->user?->email }}</div>
                            </td>
                            <td class="max-w-sm px-4 py-4 text-slate-600">
                                {{ $batch?->display_label ?? 'No batch recorded' }}
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                {{ $batchStalls ?: ($applicationStall ?: 'No stall recorded') }}
                            </td>
                            <td class="px-4 py-4">
                                @if($isResubmitted)
                                    <span class="submission-type-badge submission-type-badge--resubmitted">Revision Resubmitted</span>
                                @elseif($application->application_status === 'Submitted')
                                    <span class="submission-type-badge submission-type-badge--new">New Submission</span>
                                @else
                                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Reviewed</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <x-status-badge :status="$application->application_status" />
                            </td>
                            <td class="px-4 py-4">
                                <div>{{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}</div>
                                @if($isResubmitted)
                                    <div class="mt-1 text-xs font-semibold text-orange-700">
                                        Resubmitted {{ optional($application->revision_resubmitted_at)->format('M d, Y h:i A') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.applications.show', $application) }}" class="app-button app-button--secondary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No applications found for the selected filter.</td>
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
