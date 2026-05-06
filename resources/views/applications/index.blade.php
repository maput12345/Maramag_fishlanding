@extends('layouts.applicant')

@php
    $openingsCount = $openings->count();
    $currentApplicationStatus = $currentApplication?->application_status ?? 'No submission yet';
@endphp

@section('content')
<div class="portal-page portal-page--broker-aligned">
    <div class="portal-stage portal-stage--broker-aligned dashboard-shell">
        <div class="applicant-dashboard-header">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Applicant Dashboard</h1>
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

        <div class="metric-grid applicant-metric-grid">
            <div class="metric-card metric-card--primary">
                <div class="metric-card__row">
                    <div>
                        <p class="metric-card__eyebrow">Open Stall Applications</p>
                        <p class="metric-card__value">{{ number_format($openingsCount) }}</p>
                        <p class="metric-card__meta">{{ $openingsCount === 1 ? 'Opening available now' : 'Openings available now' }}</p>
                    </div>
                    <span class="metric-card__icon">
                        <x-heroicon-o-building-storefront />
                    </span>
                </div>
            </div>

            <div class="metric-card metric-card--success">
                <div class="metric-card__row">
                    <div>
                        <p class="metric-card__eyebrow">Current Application Status</p>
                        <div class="mt-2">
                            <x-status-badge :status="$currentApplicationStatus" size="lg" />
                        </div>
                        <p class="metric-card__meta">{{ $currentApplication ? 'Track details under My Applications' : 'Submit when a stall is open' }}</p>
                    </div>
                    <span class="metric-card__icon">
                        <x-heroicon-o-document-check />
                    </span>
                </div>
            </div>
        </div>

        <section id="open-stalls" class="panel-card applicant-panel-card">
            <div class="panel-card__inner">
            <div class="panel-card__header applicant-panel-card__header">
                <div>
                    <h2 class="panel-card__title">Open Stall Applications</h2>
                    <p class="panel-card__hint">Submit one application for the current vacant stalls. LEEO will assign the final stall after review and bidding.</p>
                </div>
                <span class="portal-count-pill">{{ $openingsCount }} {{ $openingsCount === 1 ? 'opening' : 'openings' }}</span>
            </div>

            @if($openingsCount > 0)
                <div class="applicant-action-strip">
                    @if($currentApplication)
                        <div class="portal-inline-alert portal-inline-alert--success">
                            You already submitted an application for the current open stalls.
                        </div>
                    @elseif($primaryOpening)
                        <a href="{{ route('applications.create', $primaryOpening) }}" class="portal-button portal-button--primary portal-button--cta">
                            <x-heroicon-o-paper-airplane class="portal-button__icon" />
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
                    <article class="portal-card portal-card--stall applicant-stall-card">
                        <div class="portal-card__split">
                            <div>
                                <p class="portal-card__eyebrow">Vacant Stall</p>
                                <h3 class="portal-card__title">{{ $opening->stall?->display_name ?? 'Unassigned Stall' }}</h3>
                            </div>
                            <x-status-badge :status="$opening->opening_status" />
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

                            @if($opening->stall?->address || $opening->stall?->area_sqm)
                                <div class="portal-detail-item">
                                    <div class="portal-detail-item__icon">
                                        <x-heroicon-o-document-text class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="portal-detail-item__label">Location and Size</p>
                                        <p class="portal-detail-item__value">
                                            {{ $opening->stall->address ?: 'No address recorded' }}
                                            @if($opening->stall->area_sqm)
                                                - {{ number_format((float) $opening->stall->area_sqm, 2) }} sqm
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="applicant-card-actions">
                            @if(count($stallGallery) > 0)
                                <button
                                    type="button"
                                    class="portal-button portal-button--secondary"
                                    data-stall-gallery-open="{{ $opening->id }}"
                                >
                                    <x-heroicon-o-photo class="portal-button__icon" />
                                    <span>View Photos</span>
                                </button>
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
            </div>
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
