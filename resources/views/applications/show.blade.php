@extends('layouts.applicant')

@section('content')
<div class="portal-page">
    <div class="portal-stage portal-stage--form">
        @if(session('success') || session('error') || session('info'))
            <div class="portal-alert-stack">
                @if(session('success'))
                    <div class="portal-alert portal-alert--success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="portal-alert portal-alert--error">{{ session('error') }}</div>
                @endif

                @if(session('info'))
                    <div class="portal-alert portal-alert--info">{{ session('info') }}</div>
                @endif
            </div>
        @endif

        <section class="portal-section-card">
            <div class="portal-section-card__header">
                <div>
                    <p class="portal-section-card__eyebrow">Application Record</p>
                    <h1 class="portal-section-card__title">{{ $application->selectedStall?->display_name ?? 'Open Stall Application' }}</h1>
                    <p class="portal-section-card__description">Review the submitted details, document records, and current LEEO status for this application.</p>
                </div>
                <x-status-badge :status="$application->application_status" />
            </div>

            <div class="portal-application-card__meta">
                <span>
                    <x-heroicon-o-calendar-days class="h-4 w-4" />
                    Submitted {{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'Pending timestamp' }}
                </span>
                <span>
                    <x-heroicon-o-user class="h-4 w-4" />
                    {{ $application->name }}
                </span>
                <span>
                    <x-heroicon-o-document-text class="h-4 w-4" />
                    {{ $application->requirements->count() }} requirement{{ $application->requirements->count() === 1 ? '' : 's' }} on record
                </span>
            </div>

            @if($application->application_status === 'Needs Revision')
                <div class="portal-inline-alert portal-inline-alert--warning">
                    LEEO requested corrections for this application. Review the remarks, update the needed details or documents, then resubmit it for another review.
                </div>
                <div class="portal-form-actions">
                    <a href="{{ route('applications.edit', $application) }}" class="portal-button portal-button--primary portal-button--cta">
                        <span>Edit Application</span>
                    </a>
                </div>
            @elseif($application->application_status === 'Rejected')
                <div class="portal-inline-alert portal-inline-alert--error">
                    This application was rejected by LEEO. Check the remarks below for the reason. Documents marked as not individually reviewed were not the specific rejection item.
                </div>
            @endif
        </section>

        <div class="portal-record-layout">
            <section class="portal-section-card">
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Applicant Profile</p>
                        <h2 class="portal-section-card__title">Application Details</h2>
                        <p class="portal-section-card__description">This mirrors the information you submitted through the broker application form.</p>
                    </div>
                </div>

                <div class="portal-form-grid portal-form-grid--requirement">
                    <div class="portal-field">
                        <label class="portal-field__label">Full Name</label>
                        <div class="portal-input portal-record-value">{{ $application->name }}</div>
                    </div>

                    <div class="portal-field">
                        <label class="portal-field__label">Business Name</label>
                        <div class="portal-input portal-record-value">{{ $application->business_name ?: 'N/A' }}</div>
                    </div>

                    <div class="portal-field">
                        <label class="portal-field__label">Contact Number</label>
                        <div class="portal-input portal-record-value">{{ $application->contact_number ?: 'N/A' }}</div>
                    </div>

                    <div class="portal-field">
                        <label class="portal-field__label">Review Date</label>
                        <div class="portal-input portal-record-value">{{ optional($application->review_date)->format('M d, Y h:i A') ?? 'Pending' }}</div>
                    </div>

                    <div class="portal-field portal-field--wide">
                        <label class="portal-field__label">Address</label>
                        <div class="portal-input portal-record-value portal-record-value--multiline">{{ $application->address ?: 'N/A' }}</div>
                    </div>

                    <div class="portal-field portal-field--wide">
                        <label class="portal-field__label">Remarks</label>
                        <div class="portal-input portal-record-value portal-record-value--multiline">{{ $application->remarks ?: 'No review remarks yet.' }}</div>
                    </div>
                </div>
            </section>

            <section class="portal-section-card">
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Review Flow</p>
                        <h2 class="portal-section-card__title">Review Timeline</h2>
                        <p class="portal-section-card__description">Track who reviewed the application and whether a winner has already been recorded.</p>
                    </div>
                </div>

                <div class="portal-detail-list portal-record-timeline">
                    <div class="portal-detail-item">
                        <div class="portal-detail-item__icon">
                            <x-heroicon-o-user-circle class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="portal-detail-item__label">Reviewed By</p>
                            <p class="portal-detail-item__value">{{ $application->reviewedBy?->name ?? 'Waiting for LEEO review' }}</p>
                        </div>
                    </div>

                    <div class="portal-detail-item">
                        <div class="portal-detail-item__icon portal-detail-item__icon--gold">
                            <x-heroicon-o-calendar-days class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="portal-detail-item__label">Review Date</p>
                            <p class="portal-detail-item__value">{{ optional($application->review_date)->format('M d, Y h:i A') ?? 'Pending' }}</p>
                        </div>
                    </div>

                    <div class="portal-detail-item">
                        <div class="portal-detail-item__icon">
                            <x-heroicon-o-trophy class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="portal-detail-item__label">Winner Selection</p>
                            <p class="portal-detail-item__value">{{ $application->selectedBy?->name ?? 'Not yet selected' }}</p>
                        </div>
                    </div>

                    <div class="portal-detail-item">
                        <div class="portal-detail-item__icon portal-detail-item__icon--gold">
                            <x-heroicon-o-clock class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="portal-detail-item__label">Decision Timestamp</p>
                            <p class="portal-detail-item__value">{{ optional($application->selected_at)->format('M d, Y h:i A') ?? 'Pending offline bidding result' }}</p>
                        </div>
                    </div>
                </div>

                @if($application->broker)
                    <div class="portal-inline-alert portal-inline-alert--success">
                        Broker account activated and assigned to {{ $application->broker->stall?->display_name ?? 'the winning stall' }}.
                    </div>
                @endif
            </section>
        </div>

        <section class="portal-section-card">
            <div class="portal-section-card__header">
                <div>
                    <p class="portal-section-card__eyebrow">Submitted Documents</p>
                    <h2 class="portal-section-card__title">Requirement Records</h2>
                    <p class="portal-section-card__description">Review each uploaded requirement together with its current verification status and remarks.</p>
                </div>
                <span class="portal-count-pill">{{ $application->requirements->count() }} {{ $application->requirements->count() === 1 ? 'record' : 'records' }}</span>
            </div>

            <div class="portal-requirement-grid">
                @forelse($application->requirements as $requirement)
                    @php
                        $requirementStatus = $requirement->verification_status;
                        $showOverallRejectionContext = $application->application_status === 'Rejected'
                            && $requirementStatus === 'Pending';
                        $requirementStatusLabel = $showOverallRejectionContext
                            ? 'Not Individually Reviewed'
                            : null;
                        $requirementRemarks = $requirement->remarks;

                        if ($showOverallRejectionContext && blank($requirementRemarks)) {
                            $requirementRemarks = 'No document-specific issue recorded. See the LEEO remarks above for the rejection reason.';
                        }
                    @endphp
                    <article class="portal-requirement-card">
                        <div class="portal-requirement-card__header">
                            <div>
                                <div class="portal-requirement-card__badges">
                                    <x-status-badge :status="$showOverallRejectionContext ? 'N/A' : $requirementStatus" :label="$requirementStatusLabel" />
                                </div>
                                <h3 class="portal-requirement-card__title">{{ $requirement->display_name }}</h3>
                                @if($requirement->is_additional && $requirement->custom_description)
                                    <p class="portal-requirement-card__description">{{ $requirement->custom_description }}</p>
                                @endif
                                <p class="portal-requirement-card__description">
                                    Uploaded {{ optional($requirement->uploaded_at)->format('M d, Y h:i A') ?? 'N/A' }}
                                </p>
                            </div>

                            @if($requirement->file_url)
                                <a href="{{ $requirement->file_url }}" target="_blank" rel="noopener" class="portal-button portal-button--secondary">
                                    <span>View File</span>
                                </a>
                            @endif
                        </div>

                        <div class="portal-form-grid portal-form-grid--requirement">
                            <div class="portal-field portal-field--wide">
                                <label class="portal-field__label">Requirement Remarks</label>
                                <div class="portal-input portal-record-value portal-record-value--multiline">{{ $requirementRemarks ?: 'No remarks recorded.' }}</div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="portal-empty portal-empty--wide">
                        <div class="portal-empty__icon">
                            <x-heroicon-o-document class="h-7 w-7" />
                        </div>
                        <h3 class="portal-empty__title">No requirement records found</h3>
                        <p class="portal-empty__description">Uploaded requirement details will appear here once documents are attached to the application.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
