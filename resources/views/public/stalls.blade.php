@extends('public.layout')

@section('title', 'Vacant Stalls & Requirements')

@php
    $sharedApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_BOTH;
    $naturalApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_NATURAL;
    $juridicalApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_JURIDICAL;
    $openingsCount = $openings->count();
    $publicRequirementTypes = $publicRequirementTypes ?? collect();
    $naturalPersonRequirements = $publicRequirementTypes
        ->filter(fn ($requirementType) => in_array($requirementType->audience ?: $sharedApplicantType, [$naturalApplicantType, $sharedApplicantType], true))
        ->values();
    $juridicalPersonRequirements = $publicRequirementTypes
        ->filter(fn ($requirementType) => in_array($requirementType->audience ?: $sharedApplicantType, [$juridicalApplicantType, $sharedApplicantType], true))
        ->values();
@endphp

@section('public-content')
    <style>
        .vacant-stalls-page {
            display: grid;
            gap: 0.9rem;
        }

        .vacant-status-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 1rem;
            background: rgba(2, 6, 23, 0.62);
            box-shadow: 0 22px 52px rgba(2, 6, 23, 0.22);
            color: #e2e8f0;
            padding: 1rem 1.1rem;
            backdrop-filter: blur(14px);
        }

        .vacant-status-card__eyebrow,
        .requirements-guide__eyebrow {
            margin: 0 0 0.5rem;
            color: #fde68a;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .vacant-status-card h2,
        .requirements-guide h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(1.2rem, 2.1vw, 1.55rem);
            font-weight: 900;
            line-height: 1.12;
        }

        .vacant-status-card p,
        .requirements-guide p {
            margin: 0.75rem 0 0;
            max-width: 48rem;
            color: rgba(226, 232, 240, 0.82);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .vacant-status-card__count {
            min-width: 7rem;
            border-radius: 0.85rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.8rem;
            text-align: center;
        }

        .vacant-status-card__count strong {
            display: block;
            color: #fff;
            font-size: clamp(2.25rem, 4.5vw, 3.25rem);
            font-weight: 900;
            line-height: 0.95;
        }

        .vacant-status-card__count span {
            display: block;
            margin-top: 0.4rem;
            color: rgba(226, 232, 240, 0.78);
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vacant-stall-list-card,
        .requirements-guide,
        .public-next-step {
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 1rem;
            background: rgba(2, 6, 23, 0.58);
            box-shadow: 0 22px 52px rgba(2, 6, 23, 0.22);
            color: #e2e8f0;
            padding: 1rem;
            backdrop-filter: blur(14px);
        }

        .vacant-stall-list-card__header,
        .requirements-guide__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .vacant-stall-list-card__header h3,
        .requirement-card h3 {
            margin: 0;
            color: #fff;
            font-size: 0.98rem;
            font-weight: 900;
        }

        .vacant-stall-list-card__header span,
        .requirement-card__count {
            flex-shrink: 0;
            border-radius: 999px;
            background: rgba(253, 230, 138, 0.14);
            padding: 0.3rem 0.55rem;
            color: #fde68a;
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .requirements-guide__grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .requirement-card {
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 0.85rem;
            background: rgba(15, 23, 42, 0.58);
            padding: 0.85rem;
        }

        .requirement-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.65rem;
        }

        .requirement-card__hint {
            margin: 0.25rem 0 0;
            color: rgba(226, 232, 240, 0.68);
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .requirement-checklist {
            display: grid;
            gap: 0.45rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .requirement-checklist li {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.7rem;
            background: rgba(255, 255, 255, 0.07);
            padding: 0.65rem 0.7rem;
        }

        .requirement-checklist__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.65rem;
        }

        .requirement-checklist strong {
            color: #fff;
            font-size: 0.84rem;
            line-height: 1.35;
        }

        .requirement-checklist em {
            flex-shrink: 0;
            border-radius: 999px;
            background: rgba(220, 252, 231, 0.12);
            padding: 0.2rem 0.45rem;
            color: #bbf7d0;
            font-size: 0.66rem;
            font-style: normal;
            font-weight: 900;
            text-transform: uppercase;
        }

        .requirement-checklist em.is-optional {
            background: rgba(226, 232, 240, 0.12);
            color: rgba(226, 232, 240, 0.78);
        }

        .requirement-checklist p {
            margin: 0.45rem 0 0;
            color: rgba(226, 232, 240, 0.66);
            font-size: 0.76rem;
            line-height: 1.42;
        }

        .public-next-step {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .public-next-step p {
            margin: 0;
            color: rgba(226, 232, 240, 0.82);
            font-size: 0.88rem;
            line-height: 1.55;
        }

        @media (max-width: 900px) {
            .vacant-status-card,
            .requirements-guide__grid,
            .public-next-step {
                grid-template-columns: 1fr;
            }

            .vacant-status-card {
                display: block;
            }

            .vacant-status-card__count {
                margin-top: 1rem;
                text-align: left;
            }

            .public-next-step {
                display: grid;
            }
        }
    </style>

    <section class="public-page-banner public-page-banner--compact">
        <div class="public-container public-page-banner__inner">
            <div class="public-page-banner__content">
                <span class="public-kicker">Stall and Requirements</span>
                <h1 class="public-page-title">Vacant Stalls</h1>
            </div>
        </div>
    </section>

    <section class="public-section public-section--soft public-section--tight">
        <div class="public-container">
            <div class="vacant-stalls-page">
                <article class="vacant-status-card">
                    <div>
                        <p class="vacant-status-card__eyebrow">Vacancy Status</p>
                        <h2>
                            {{ $openingsCount > 0
                                ? 'Vacant stalls are available'
                                : 'No vacant stall is currently open' }}
                        </h2>
                        <p>
                            {{ $openingsCount > 0
                                ? 'Review the available stall list below. Applicants may log in or register to submit an application during the official LEEO schedule.'
                                : 'Please check this page again for the next official LEEO application schedule. You can prepare the requirements below in advance.' }}
                        </p>
                    </div>
                    <div class="vacant-status-card__count">
                        <strong>{{ $openingsCount }}</strong>
                        <span>{{ \Illuminate\Support\Str::plural('vacant stall', $openingsCount) }}</span>
                    </div>
                </article>

                @if($openingsCount > 0)
                    <article class="vacant-stall-list-card">
                        <div class="vacant-stall-list-card__header">
                            <h3>Vacant Stall List</h3>
                            <span>{{ $openingsCount }} {{ \Illuminate\Support\Str::plural('vacant stall', $openingsCount) }}</span>
                        </div>
                        <ul class="public-vacant-stall-list">
                            @foreach($openings as $opening)
                                <li>
                                    <strong>{{ $opening->stall?->display_name ?? 'Unassigned Stall' }}</strong>
                                    <span>{{ $opening->opening_status }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endif

                <section class="requirements-guide" aria-labelledby="requirements-guide-title">
                    <div class="requirements-guide__header">
                        <div>
                            <p class="requirements-guide__eyebrow">Preparation Guide</p>
                            <h2 id="requirements-guide-title">Prepare Your Requirements</h2>
                            <p>Choose the checklist that matches your applicant type.</p>
                        </div>
                    </div>

                    <div class="requirements-guide__grid">
                        <article class="requirement-card">
                            <div class="requirement-card__header">
                                <div>
                                    <h3>Natural Person</h3>
                                    <p class="requirement-card__hint">For individual applicants.</p>
                                </div>
                                <span class="requirement-card__count">{{ $naturalPersonRequirements->count() }} items</span>
                            </div>

                            <ul class="requirement-checklist">
                                @forelse($naturalPersonRequirements as $requirement)
                                    <li>
                                        <div class="requirement-checklist__top">
                                            <strong>{{ $requirement->requirement_name }}</strong>
                                            <em @class(['is-optional' => !$requirement->is_required])>
                                                {{ $requirement->is_required ? 'Required' : 'Optional' }}
                                            </em>
                                        </div>
                                        @if($requirement->description)
                                            <p>{{ $requirement->description }}</p>
                                        @endif
                                    </li>
                                @empty
                                    <li>
                                        <div class="requirement-checklist__top">
                                            <strong>No natural person requirements have been published yet.</strong>
                                        </div>
                                    </li>
                                @endforelse
                            </ul>
                        </article>

                        <article class="requirement-card">
                            <div class="requirement-card__header">
                                <div>
                                    <h3>Juridical Person</h3>
                                    <p class="requirement-card__hint">For corporations, partnerships, and registered business entities.</p>
                                </div>
                                <span class="requirement-card__count">{{ $juridicalPersonRequirements->count() }} items</span>
                            </div>

                            <ul class="requirement-checklist">
                                @forelse($juridicalPersonRequirements as $requirement)
                                    <li>
                                        <div class="requirement-checklist__top">
                                            <strong>{{ $requirement->requirement_name }}</strong>
                                            <em @class(['is-optional' => !$requirement->is_required])>
                                                {{ $requirement->is_required ? 'Required' : 'Optional' }}
                                            </em>
                                        </div>
                                        @if($requirement->description)
                                            <p>{{ $requirement->description }}</p>
                                        @endif
                                    </li>
                                @empty
                                    <li>
                                        <div class="requirement-checklist__top">
                                            <strong>No juridical person requirements have been published yet.</strong>
                                        </div>
                                    </li>
                                @endforelse
                            </ul>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
