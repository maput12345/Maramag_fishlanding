@extends('layouts.app')

@php
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
                    <p class="portal-section-card__description">Submit one application for the current vacant stalls. LEEO will assign the final stall after review and bidding.</p>
                </div>
                <span class="portal-count-pill">{{ $openingsCount }} {{ $openingsCount === 1 ? 'opening' : 'openings' }}</span>
            </div>

            @if($openingsCount > 0)
                <div class="mb-6">
                    @if($currentApplication)
                        <div class="portal-inline-alert portal-inline-alert--success">
                            You already submitted an application for the current open stalls.
                        </div>
                    @elseif($primaryOpening)
                        <a href="{{ route('applications.create', $primaryOpening) }}" class="portal-button portal-button--primary portal-button--cta">
                            <span>Apply Now</span>
                        </a>
                    @endif
                </div>
            @endif

            <div class="portal-stall-grid">
                @forelse($openings as $opening)
                    @php
                        $stallGallery = $opening->stall?->gallery_image_urls ?? [];
                    @endphp
                    <article class="portal-card portal-card--stall">
                        @if(count($stallGallery) > 0)
                            <div class="mb-5">
                                <button
                                    type="button"
                                    class="portal-button portal-button--secondary"
                                    data-stall-gallery-open="{{ $opening->id }}"
                                >
                                    <x-heroicon-o-photo class="portal-button__icon" />
                                    <span>View Photos</span>
                                </button>
                            </div>
                        @endif

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
                                    <p class="portal-detail-item__label">Application Pool</p>
                                    <p class="portal-detail-item__value">One shared form</p>
                                </div>
                            </div>

                            @if($opening->stall?->remarks)
                                <div class="portal-detail-item">
                                    <div class="portal-detail-item__icon">
                                        <x-heroicon-o-document-text class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="portal-detail-item__label">Description</p>
                                        <p class="portal-detail-item__value">{{ $opening->stall->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </article>

                    @if(count($stallGallery) > 0)
                        <div
                            class="stall-gallery-modal"
                            data-stall-gallery-modal="{{ $opening->id }}"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="stall-gallery-title-{{ $opening->id }}"
                            hidden
                        >
                            <div class="stall-gallery-modal__backdrop" data-stall-gallery-close></div>
                            <div class="stall-gallery-modal__panel">
                                <div class="stall-gallery-modal__header">
                                    <div>
                                        <p class="portal-section-card__eyebrow">Stall Photos</p>
                                        <h3 id="stall-gallery-title-{{ $opening->id }}" class="stall-gallery-modal__title">
                                            {{ $opening->stall?->display_name ?? 'Stall Gallery' }}
                                        </h3>
                                    </div>
                                    <button type="button" class="stall-gallery-modal__close" data-stall-gallery-close aria-label="Close stall photos">
                                        &times;
                                    </button>
                                </div>

                                <div class="stall-gallery-modal__grid">
                                    @foreach($stallGallery as $galleryImage)
                                        <a href="{{ $galleryImage }}" target="_blank" rel="noopener" class="stall-gallery-modal__image-link">
                                            <img
                                                src="{{ $galleryImage }}"
                                                alt="{{ $opening->stall?->display_name ?? 'Stall' }} photo {{ $loop->iteration }}"
                                                class="stall-gallery-modal__image"
                                            >
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
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
                    $applicationStallLabel = $application->selectedStall?->display_name
                        ?? ($application->application_status === 'Winner'
                            ? ($application->applicationOpening?->stall?->display_name ?? 'Awarded stall')
                            : 'Open stall application');
                @endphp

                <article class="portal-card portal-card--application">
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
                            <span class="portal-status-badge {{ $statusTone }}">{{ $application->application_status }}</span>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openButtons = document.querySelectorAll('[data-stall-gallery-open]');
    const closeButtons = document.querySelectorAll('[data-stall-gallery-close]');

    const closeOpenGallery = () => {
        document.querySelectorAll('[data-stall-gallery-modal]').forEach((modal) => {
            modal.hidden = true;
        });

        document.body.classList.remove('stall-gallery-modal-is-open');
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.querySelector(`[data-stall-gallery-modal="${button.dataset.stallGalleryOpen}"]`);

            if (!modal) {
                return;
            }

            modal.hidden = false;
            document.body.classList.add('stall-gallery-modal-is-open');
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeOpenGallery);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeOpenGallery();
        }
    });
});
</script>
@endsection
