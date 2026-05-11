@extends('public.layout')

@section('title', 'Services')

@section('public-content')
    <section class="public-page-banner public-page-banner--compact">
        <div class="public-container public-page-banner__inner">
            <div class="public-page-banner__content">
                <span class="public-kicker">Services</span>
                <h1>Public and Administrative Services</h1>
                <p>
                    A connected platform for stall applications, broker transactions, fish box tracking, sales monitoring, and LEEO administration.
                </p>
            </div>
        </div>
    </section>

    <section class="public-section public-section--soft public-section--tight">
        <div class="public-container">
            <div class="public-card-grid public-services-grid">
                @foreach($services as $service)
                    <article class="public-card">
                        <span class="public-icon" aria-hidden="true">
                            @switch($service['icon'])
                                @case('building')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M9 17h1"/></svg>
                                    @break
                                @case('receipt')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                                    @break
                                @case('box')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                                    @break
                                @case('chart')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                                    @break
                                @default
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                            @endswitch
                        </span>
                        <h3>{{ $service['title'] }}</h3>
                        <p>{{ $service['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
