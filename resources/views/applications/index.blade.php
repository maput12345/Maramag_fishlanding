@extends('layouts.app')

@php
    $appliedOpeningIds = $applications->pluck('application_opening_id')->all();
    $openingsCount = $openings->count();
    $applicationsCount = $applications->count();
@endphp

@section('body-class', 'portal-shell theme-admin')

@section('content')
<div class="portal-page">
    <div class="portal-stage">
        <div class="portal-topbar">
            <div class="portal-topbar__brand">
                <span class="portal-brand-pill">LEEO Digital Services</span>
                <div>
                    <p class="portal-topbar__title">Broker Application Portal</p>
                </div>
            </div>

            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="portal-button portal-button--ghost">
                <x-heroicon-o-arrow-right-on-rectangle class="portal-button__icon" />
                <span>Logout</span>
            </a>

            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>
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

        <section id="open-stalls" class="portal-section-card">
            <div class="portal-section-card__header">
                <div>
                    <p class="portal-section-card__eyebrow">Available Openings</p>
                    <h2 class="portal-section-card__title">Open Stall Applications</h2>
                </div>
                <span class="portal-count-pill">{{ $openingsCount }} {{ $openingsCount === 1 ? 'opening' : 'openings' }}</span>
            </div>

            <div class="portal-stall-grid">
                @forelse($openings as $opening)
                    <article class="portal-card portal-card--stall">
                        <div class="portal-card__split">
                            <div>
                                <p class="portal-card__eyebrow">Vacant Stall</p>
                                <h3 class="portal-card__title">{{ $opening->stall?->display_name ?? 'Unassigned Stall' }}</h3>
                            </div>
                            <span class="portal-status-badge portal-status-badge--open">{{ $opening->opening_status }}</span>
                        </div>

                        <div class="portal-detail-list">
                            <div class="portal-detail-item">
                                <div class="portal-detail-item__icon">
                                    <x-heroicon-o-calendar-days class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="portal-detail-item__label">Application Window</p>
                                    <p class="portal-detail-item__value">
                                        {{ optional($opening->start_date)->format('M d, Y') }} to {{ optional($opening->end_date)->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="portal-detail-item">
                                <div class="portal-detail-item__icon portal-detail-item__icon--gold">
                                    <x-heroicon-o-document-text class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="portal-detail-item__label">Submitted Applications</p>
                                    <p class="portal-detail-item__value">{{ $opening->broker_applications_count }}</p>
                                </div>
                            </div>
                        </div>

                        @if(in_array($opening->id, $appliedOpeningIds, true))
                            <div class="portal-inline-alert portal-inline-alert--success">
                                You already submitted an application for this stall.
                            </div>
                        @else
                            <a href="{{ route('applications.create', $opening) }}" class="portal-button portal-button--primary portal-button--cta">
                                <span>Apply Now</span>
                            </a>
                        @endif
                    </article>
                @empty
                    <div class="portal-empty portal-empty--wide">
                        <div class="portal-empty__icon">
                            <x-heroicon-o-building-storefront class="h-7 w-7" />
                        </div>
                        <h3 class="portal-empty__title">No stall openings are available right now</h3>
                    </div>
                @endforelse
            </div>
        </section>

        <section id="my-applications" class="portal-section-card">
            <div class="portal-section-card__header">
                <div>
                    <p class="portal-section-card__eyebrow">Application History</p>
                    <h2 class="portal-section-card__title">My Submitted Applications</h2>
                </div>
                <span class="portal-count-pill">{{ $applicationsCount }} {{ $applicationsCount === 1 ? 'submission' : 'submissions' }}</span>
            </div>

            @forelse($applications as $application)
                @php
                    $statusTone = match ($application->application_status) {
                        'Qualified', 'Winner' => 'portal-status-badge--success',
                        'Needs Revision' => 'portal-status-badge--warning',
                        'Rejected', 'Not Selected' => 'portal-status-badge--danger',
                        default => 'portal-status-badge--neutral',
                    };
                @endphp

                <article class="portal-card portal-card--application">
                    <div class="portal-card__split portal-card__split--stack-on-mobile">
                        <div class="portal-application-card__body">
                            <p class="portal-card__eyebrow">{{ $application->applicationOpening?->stall?->display_name ?? 'Stall Opening' }}</p>
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
                            <span class="portal-status-badge {{ $statusTone }}">{{ $application->application_status }}</span>
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
                    @if($openingsCount > 0)
                        <a href="#open-stalls" class="portal-button portal-button--secondary portal-empty__action">
                            <span>Browse Open Stalls</span>
                        </a>
                    @endif
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
