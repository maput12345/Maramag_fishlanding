@extends('public.layout')

@section('title', 'Vacant Stalls & Requirements')

@php
    $openingsCount = $openings->count();
    $publishedRequirements = $openings
        ->flatMap(fn ($opening) => $opening->resolvedRequirementTypes())
        ->unique('id')
        ->values();
@endphp

@section('public-content')
    <section class="public-page-banner public-page-banner--compact">
        <div class="public-container public-page-banner__inner">
            <div class="public-page-banner__content">
                <span class="public-kicker">Stall and Requirements</span>
            </div>
        </div>
    </section>

    <section class="public-section public-section--soft public-section--tight">
        <div class="public-container">

            @if($openingsCount > 0)
                <div class="public-vacancy-summary">
                    <article class="public-vacancy-panel public-vacancy-panel--count">
                        <p class="public-card-label">Total Vacant Stalls</p>
                        <strong>{{ $openingsCount }}</strong>
                        <span>{{ \Illuminate\Support\Str::plural('stall', $openingsCount) }} currently open for application</span>
                    </article>

                    <article class="public-vacancy-panel">
                        <div class="public-vacancy-panel__head">
                            <h3>Vacant Stall List</h3>
                            <span>{{ $openingsCount }} {{ \Illuminate\Support\Str::plural('opening', $openingsCount) }}</span>
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

                    <article class="public-vacancy-panel">
                        <div class="public-vacancy-panel__head">
                            <h3>Requirements List</h3>
                            <span>For public reference</span>
                        </div>
                        <ul class="public-clean-requirements">
                            @forelse($publishedRequirements as $requirement)
                                <li>
                                    {{ $requirement->requirement_name }}
                                    @if(!($requirement->pivot?->is_required ?? $requirement->is_required ?? true))
                                        <span>if applicable</span>
                                    @endif
                                </li>
                            @empty
                                <li>No requirement list has been published yet.</li>
                            @endforelse
                        </ul>
                    </article>
                </div>
            @else
                    <article class="public-card public-empty">
                        <h3>No vacant stall is currently open for application</h3>
                        <p>Please check this page again for the next official LEEO application schedule.</p>
                    </article>
            @endif
        </div>
    </section>
@endsection
