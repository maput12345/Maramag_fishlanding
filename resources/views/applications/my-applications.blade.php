@extends('layouts.applicant')

@section('content')
<div class="portal-page portal-page--broker-aligned">
    <div class="portal-stage portal-stage--broker-aligned dashboard-shell">
        <div class="applicant-dashboard-header">
            <div class="dashboard-header">
                <span class="dashboard-kicker">Applicant Records</span>
                <div>
                    <h1 class="dashboard-title">My Applications</h1>
                    <p class="dashboard-subtitle">Track your application status and review details.</p>
                </div>
            </div>
        </div>

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

        <section class="panel-card applicant-panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header applicant-panel-card__header">
                    <div>
                        <h2 class="panel-card__title">Submitted Applications</h2>
                        <p class="panel-card__hint">Review status, submitted date, and requirement review details.</p>
                    </div>
                    <span class="portal-count-pill">{{ $applicationsCount }} {{ $applicationsCount === 1 ? 'submission' : 'submissions' }}</span>
                </div>

                @forelse($applications as $application)
                    @php
                        $applicationStallLabel = $application->selectedStall?->display_name
                            ?? ($application->application_status === 'Winner'
                                ? ($application->applicationOpening?->stall?->display_name ?? 'Awarded stall')
                                : 'Open stall application');
                    @endphp

                    <article class="portal-card portal-card--application applicant-application-card">
                        <div class="portal-card__split portal-card__split--stack-on-mobile">
                            <div class="portal-application-card__body">
                                <p class="portal-card__eyebrow">{{ $applicationStallLabel }}</p>
                                <h3 class="portal-card__title">{{ $application->name }}</h3>
                                <div class="portal-application-card__meta">
                                    <span>
                                        <x-heroicon-o-calendar-days class="h-4 w-4" />
                                        Submitted {{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'Pending timestamp' }}
                                    </span>
                                    <span>
                                        <x-heroicon-o-document-text class="h-4 w-4" />
                                        Requirement review managed by the LEEO office
                                    </span>
                                </div>
                            </div>

                            <div class="portal-application-card__actions">
                                <x-status-badge :status="$application->application_status" />
                                @if($application->application_status === 'Needs Revision')
                                    <a href="{{ route('applications.edit', $application) }}" class="portal-button portal-button--primary">
                                        <span>Edit Application</span>
                                    </a>
                                @endif
                                <a href="{{ route('applications.show', $application) }}" class="portal-button portal-button--secondary">
                                    <span>View Details</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="portal-empty">
                        <div class="portal-empty__icon">
                            <x-heroicon-o-inbox class="h-7 w-7" />
                        </div>
                        <h3 class="portal-empty__title">No submitted applications yet</h3>
                        <a href="{{ route('applications.index') }}#open-stalls" class="portal-button portal-button--secondary portal-empty__action">
                            <span>Browse Open Stalls</span>
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
