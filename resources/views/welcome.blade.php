@extends('public.layout')

@section('title', 'Maramag Fish Landing Management System')

@section('public-content')
    <section class="public-hero public-hero--compact">
        <div class="public-container public-hero__inner">
            <div class="public-hero__content">
                <span class="public-kicker">Official Website</span>
                <h1>Maramag Fish Landing Management System</h1>
                <p>
                    A secure web-based platform for stall applications, broker transactions, fish box tracking, and LEEO administration.
                </p>
                <div class="public-action-row">
                    <a href="{{ route('public.stalls') }}" class="public-button public-button--soft">View Vacant Stalls &amp; Requirements</a>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section public-section--soft public-section--tight">
        <div class="public-container">
            <div class="public-card-grid">
                <article class="public-card">
                    <span class="public-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                    </span>
                    <h3>Vacant Stall Information</h3>
                    <p>Review open stalls, schedules, bidding details, and public requirement lists before applying.</p>
                </article>
                <article class="public-card">
                    <span class="public-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <h3>Secure Access</h3>
                    <p>Applicants, brokers, and administrators continue through existing authenticated system workflows.</p>
                </article>
                <article class="public-card">
                    <span class="public-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    </span>
                    <h3>Operational Support</h3>
                    <p>The system supports stall applications, broker records, fish box tracking, and LEEO oversight.</p>
                </article>
            </div>
        </div>
    </section>
@endsection
