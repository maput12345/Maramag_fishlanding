@extends('public.layout')

@section('title', 'About Maramag Fish Landing Management System')

@section('public-content')
    <section class="public-page-banner public-page-banner--compact">
        <div class="public-container public-page-banner__inner">
            <div class="public-page-banner__content">
                <span class="public-kicker">About</span>
                <h1>About Maramag Fish Landing Management System</h1>
                <p>
                    Supporting daily fish landing operations through digital processing, clearer monitoring, and organized records.
                </p>
            </div>
        </div>
    </section>

    <section class="public-section public-section--soft public-section--tight">
        <div class="public-container">
            <div class="public-card-grid">
                <article class="public-card">
                    <span class="public-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6"/><path d="M9 16h6"/><path d="M8 3h8l4 4v14H4V3h4Z"/><path d="M16 3v5h4"/></svg>
                    </span>
                    <h3>Digital Processing</h3>
                    <p>Helps shift stall application records and requirements into a more structured online process.</p>
                </article>
                <article class="public-card">
                    <span class="public-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h8l-1 8 10-12h-8l1-8Z"/></svg>
                    </span>
                    <h3>Faster Monitoring</h3>
                    <p>Provides clearer visibility for application openings, sales activity, and fish box movement.</p>
                </article>
                <article class="public-card">
                    <span class="public-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg>
                    </span>
                    <h3>Organized Records</h3>
                    <p>Keeps operational records consistent so LEEO staff can review information with less manual tracing.</p>
                </article>
            </div>
        </div>
    </section>
@endsection
