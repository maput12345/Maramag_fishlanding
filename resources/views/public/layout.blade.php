@extends('layouts.app')

@section('body-class', 'mfl-public-shell')

@section('content')
<style>
    :root {
        --public-navy: #0f2747;
        --public-navy-deep: #071525;
        --public-blue: #1d4ed8;
        --public-gold: #d6a53a;
        --public-ink: #102033;
        --public-muted: #64748b;
        --public-border: #dbe4ef;
        --public-soft: #f4f8fc;
    }

    .mfl-public-shell {
        margin: 0;
        min-height: 100vh;
        background:
            radial-gradient(circle at 18% 16%, rgba(29, 78, 216, 0.14), transparent 24rem),
            linear-gradient(180deg, rgba(7, 21, 37, 0.88), rgba(7, 21, 37, 0.84)),
            url("{{ asset('image/background.webp') }}?v={{ filemtime(public_path('image/background.webp')) }}") center / cover fixed no-repeat;
        color: var(--public-ink);
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .public-page {
        position: relative;
        min-height: 100vh;
        overflow-x: hidden;
    }

    .public-container {
        width: min(1280px, calc(100% - 2rem));
        margin: 0 auto;
    }

    .public-header {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        border-bottom: 0;
        background: transparent;
        box-shadow: none;
        padding-top: 1.5rem;
    }

    .public-header::before {
        content: none;
    }

    .public-header__inner {
        display: flex;
        min-height: 4.75rem;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 1rem;
        background: rgba(2, 6, 23, 0.68);
        box-shadow: 0 20px 50px rgba(2, 6, 23, 0.34);
        padding: 0 1rem;
        backdrop-filter: blur(16px);
    }

    .public-brand {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 0.8rem;
        color: #fff;
        text-decoration: none;
    }

    .public-brand img {
        width: 3.5rem;
        height: 3.5rem;
        flex-shrink: 0;
        object-fit: contain;
        border: 0;
        border-radius: 0;
        background: transparent;
        padding: 0;
        box-shadow: none;
    }

    .public-brand strong {
        display: block;
        max-width: 28rem;
        font-size: 1rem;
        font-weight: 900;
        line-height: 1.25;
    }

    .public-nav {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.3rem;
    }

    .public-nav__link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.55rem;
        border-radius: 0.75rem;
        padding: 0 0.9rem;
        color: rgba(241, 245, 249, 0.9);
        font-size: 0.9rem;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .public-nav__link:hover,
    .public-nav__link--active {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }

    .public-nav__link--active {
        box-shadow: inset 0 -3px 0 var(--public-gold);
    }

    .public-nav__link--primary {
        background: #fff;
        color: var(--public-navy);
        box-shadow: 0 14px 28px rgba(2, 6, 23, 0.28);
    }

    .public-nav__link--primary:hover,
    .public-nav__link--primary.public-nav__link--active {
        background: #eaf2ff;
        color: var(--public-navy-deep);
    }

    .public-menu-button {
        display: none;
        width: 2.75rem;
        height: 2.75rem;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--public-border);
        border-radius: 0.8rem;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .public-menu-button svg {
        width: 1.3rem;
        height: 1.3rem;
    }

    .public-hero {
        position: relative;
        background: transparent;
        color: #fff;
    }

    .public-hero__inner {
        display: flex;
        min-height: 100vh;
        align-items: center;
        padding: clamp(8rem, 14vw, 10rem) 0 clamp(4rem, 8vw, 6rem);
    }

    .public-hero--compact .public-hero__inner {
        min-height: auto;
        align-items: flex-start;
        padding: clamp(8.5rem, 13vw, 9.5rem) 0 clamp(2rem, 4vw, 3rem);
    }

    .public-hero__content {
        max-width: 48rem;
    }

    .public-page-banner {
        position: relative;
        background: transparent;
        color: #fff;
    }

    .public-page-banner__inner {
        min-height: 18rem;
        display: flex;
        align-items: center;
        padding: clamp(8rem, 13vw, 9.5rem) 0 clamp(3rem, 6vw, 4.5rem);
    }

    .public-page-banner--compact .public-page-banner__inner {
        min-height: auto;
        padding: clamp(8.5rem, 13vw, 9.25rem) 0 1.35rem;
    }

    .public-page-banner__content {
        max-width: 820px;
    }

    .public-page-banner h1 {
        margin: 0.85rem 0 0;
        font-size: clamp(2.25rem, 4.5vw, 4rem);
        line-height: 1.1;
        font-weight: 850;
    }

    .public-page-banner p {
        margin: 1rem 0 0;
        max-width: 720px;
        color: rgba(226, 232, 240, 0.92);
        font-size: 1.05rem;
        line-height: 1.7;
    }

    .public-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: 1px solid rgba(214, 165, 58, 0.42);
        border-radius: 999px;
        background: rgba(214, 165, 58, 0.12);
        padding: 0.45rem 0.75rem;
        color: #fde68a;
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.15em;
        text-transform: uppercase;
    }

    .public-kicker::before {
        content: "";
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 999px;
        background: var(--public-gold);
    }

    .public-hero h1,
    .public-page-title {
        margin: 1.15rem 0 0;
        color: inherit;
        font-size: clamp(3rem, 6vw, 4.5rem);
        font-weight: 800;
        line-height: 1.08;
    }

    .public-hero p {
        max-width: 46rem;
        margin: 1.25rem 0 0;
        color: rgba(226, 232, 240, 0.92);
        font-size: clamp(1rem, 1.5vw, 1.18rem);
        line-height: 1.7;
    }

    .public-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.9rem;
        margin-top: 1.75rem;
    }

    .public-button {
        display: inline-flex;
        min-height: 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        padding: 0 1.75rem;
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .public-button:hover {
        transform: translateY(-1px);
    }

    .public-button--light {
        background: #fff;
        color: var(--public-navy);
        box-shadow: 0 18px 34px rgba(2, 8, 23, 0.24);
    }

    .public-button--outline,
    .public-button--soft {
        border: 1px solid rgba(255, 255, 255, 0.42);
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .public-button--soft {
        background: rgba(2, 6, 23, 0.18);
    }

    .public-button--navy {
        background: var(--public-navy);
        color: #fff;
        box-shadow: 0 16px 30px rgba(15, 39, 71, 0.18);
    }

    .public-button--secondary {
        border: 1px solid rgba(255, 255, 255, 0.28);
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .public-section {
        padding: clamp(3.5rem, 7vw, 5.5rem) 0;
    }

    .public-section--tight {
        padding-top: 1.25rem;
    }

    .public-section--soft {
        background: transparent;
        backdrop-filter: none;
    }

    .public-page-head {
        max-width: 820px;
        margin-bottom: 2rem;
    }

    .public-page-eyebrow {
        margin: 0;
        color: #fde68a;
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .public-page-title {
        margin-top: 0.8rem;
        color: #fff;
        font-size: clamp(2.2rem, 4vw, 3.65rem);
    }

    .public-page-copy {
        margin: 1rem 0 0;
        color: rgba(226, 232, 240, 0.9);
        font-size: 1.05rem;
        line-height: 1.8;
    }

    .public-card-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .public-card,
    .public-stall-row,
    .public-notice {
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        background: rgba(2, 6, 23, 0.58);
        box-shadow: 0 22px 52px rgba(2, 6, 23, 0.22);
        backdrop-filter: blur(14px);
        color: #e2e8f0;
    }

    .public-card {
        padding: 1.25rem;
    }

    .public-icon {
        display: inline-flex;
        width: 2.75rem;
        height: 2.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.8rem;
        background: rgba(255, 255, 255, 0.12);
        color: #fde68a;
    }

    .public-icon svg {
        width: 1.35rem;
        height: 1.35rem;
    }

    .public-card h3 {
        margin: 1rem 0 0;
        color: #fff;
        font-size: 1.05rem;
        font-weight: 900;
    }

    .public-card p {
        margin: 0.55rem 0 0;
        color: rgba(226, 232, 240, 0.82);
        font-size: 0.95rem;
        line-height: 1.65;
    }

    .public-services-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .public-stall-list {
        display: grid;
        gap: 0.85rem;
    }

    .public-vacancy-summary {
        display: grid;
        grid-template-columns: minmax(13rem, 0.45fr) minmax(0, 0.8fr) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .public-vacancy-panel {
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        background: rgba(2, 6, 23, 0.58);
        box-shadow: 0 22px 52px rgba(2, 6, 23, 0.22);
        backdrop-filter: blur(14px);
        color: #e2e8f0;
        padding: 1.15rem;
    }

    .public-vacancy-panel--count {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 11rem;
    }

    .public-vacancy-panel--count strong {
        display: block;
        color: #fff;
        font-size: clamp(4rem, 8vw, 6.25rem);
        font-weight: 900;
        line-height: 0.95;
    }

    .public-vacancy-panel--count span {
        margin-top: 0.75rem;
        color: rgba(226, 232, 240, 0.82);
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .public-vacancy-panel__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .public-vacancy-panel__head h3 {
        margin: 0;
        color: #fff;
        font-size: 1.05rem;
        font-weight: 900;
    }

    .public-vacancy-panel__head span {
        flex-shrink: 0;
        color: #fde68a;
        font-size: 0.78rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .public-vacant-stall-list,
    .public-clean-requirements {
        display: grid;
        gap: 0.55rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .public-vacant-stall-list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0.55rem;
    }

    .public-vacant-stall-list li:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .public-vacant-stall-list strong {
        color: #fff;
        font-size: 0.98rem;
    }

    .public-vacant-stall-list span {
        flex-shrink: 0;
        border-radius: 999px;
        background: #dcfce7;
        padding: 0.32rem 0.58rem;
        color: #166534;
        font-size: 0.72rem;
        font-weight: 900;
    }

    .public-clean-requirements li {
        position: relative;
        padding-left: 1.05rem;
        color: rgba(226, 232, 240, 0.86);
        font-size: 0.94rem;
        line-height: 1.45;
    }

    .public-clean-requirements li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.55rem;
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 999px;
        background: var(--public-gold);
    }

    .public-clean-requirements span {
        color: #fde68a;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .public-stall-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(18rem, 0.58fr);
        gap: 1rem;
        padding: 1rem;
    }

    .public-stall-row__main {
        display: flex;
        min-width: 0;
        gap: 1rem;
    }

    .public-stall-row__title {
        width: min(15rem, 34%);
        flex-shrink: 0;
    }

    .public-card-label {
        margin: 0 0 0.35rem;
        color: #fde68a;
        font-size: 0.74rem;
        font-weight: 900;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .public-stall-row h3 {
        margin: 0;
        color: #fff;
        font-size: 1.25rem;
        font-weight: 900;
    }

    .public-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #dcfce7;
        padding: 0.42rem 0.7rem;
        color: #166534;
        font-size: 0.76rem;
        font-weight: 900;
        margin-top: 0.65rem;
    }

    .public-stall-meta {
        display: grid;
        flex: 1;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem 1rem;
        margin: 0;
        min-width: 0;
    }

    .public-stall-meta div {
        min-width: 0;
    }

    .public-stall-meta dt {
        display: block;
        color: rgba(226, 232, 240, 0.72);
        font-size: 0.74rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .public-stall-meta dd {
        display: block;
        margin-top: 0.35rem;
        color: #fff;
        font-size: 0.93rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .public-stall-meta__wide {
        grid-column: 1 / -1;
    }

    .public-requirement-list {
        border-left: 1px solid rgba(255, 255, 255, 0.14);
        padding-left: 1rem;
    }

    .public-requirement-list h4 {
        margin: 0 0 0.75rem;
        color: #fff;
        font-size: 0.96rem;
        font-weight: 900;
    }

    .public-requirement-list__items {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .public-requirement-list__items span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        padding: 0.4rem 0.65rem;
        color: rgba(226, 232, 240, 0.84);
        font-size: 0.84rem;
        line-height: 1.3;
    }

    .public-requirement-list__items small {
        color: #fde68a;
        font-size: 0.74rem;
        font-weight: 800;
    }

    .public-notice {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1.2rem;
        padding: 1.15rem 1.25rem;
    }

    .public-notice strong {
        display: block;
        color: #fff;
    }

    .public-notice span {
        display: block;
        margin-top: 0.2rem;
        color: rgba(226, 232, 240, 0.78);
        font-size: 0.9rem;
    }

    .public-empty {
        grid-column: 1 / -1;
        padding: 2rem;
        text-align: center;
    }

    .public-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(2, 6, 23, 0.48);
        backdrop-filter: blur(14px);
        padding: 1.4rem 0;
        color: rgba(226, 232, 240, 0.82);
        font-size: 0.88rem;
    }

    .public-footer .public-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 1100px) {
        .public-services-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .public-menu-button {
            display: inline-flex;
        }

        .public-header__inner {
            min-height: 4.5rem;
            flex-wrap: wrap;
            padding: 0.75rem;
        }

        .public-nav {
            display: none;
            width: 100%;
            align-items: stretch;
            flex-direction: column;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            padding-top: 0.75rem;
        }

        .public-nav.is-open {
            display: flex;
        }

        .public-nav__link {
            justify-content: flex-start;
        }

        .public-hero__inner {
            min-height: 100vh;
            padding: 9rem 0 3rem;
        }

        .public-page-banner__inner {
            min-height: auto;
            padding-top: 9rem;
        }

        .public-card-grid,
        .public-vacancy-summary,
        .public-stall-row {
            grid-template-columns: 1fr;
        }

        .public-stall-row__main {
            flex-direction: column;
        }

        .public-stall-row__title {
            width: 100%;
        }

        .public-requirement-list {
            border-left: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            padding-left: 0;
            padding-top: 1rem;
        }

        .public-notice,
        .public-footer .public-container {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 620px) {
        .public-container {
            width: min(100% - 1.25rem, 1280px);
        }

        .public-brand img {
            width: 2.6rem;
            height: 2.6rem;
        }

        .public-brand strong {
            max-width: 14rem;
            font-size: 0.84rem;
        }

        .public-hero h1 {
            font-size: clamp(2.35rem, 12vw, 3.35rem);
        }

        .public-action-row,
        .public-button {
            width: 100%;
        }

        .public-stall-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="public-page">
    <header class="public-header">
        <div class="public-container public-header__inner">
            <a href="{{ route('public.home') }}" class="public-brand">
                <img src="{{ asset('image/logo-small.png') }}" alt="Maramag Fish Landing logo">
                <strong>Maramag Fish Landing Management System</strong>
            </a>

            <button class="public-menu-button" type="button" data-public-menu-toggle aria-label="Toggle navigation" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 7h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 17h16"></path>
                </svg>
            </button>

            <nav class="public-nav" data-public-menu aria-label="Public navigation">
                <a href="{{ route('public.home') }}" class="public-nav__link {{ request()->routeIs('public.home') ? 'public-nav__link--active' : '' }}">Home</a>
                <a href="{{ route('public.about') }}" class="public-nav__link {{ request()->routeIs('public.about') ? 'public-nav__link--active' : '' }}">About</a>
                <a href="{{ route('public.services') }}" class="public-nav__link {{ request()->routeIs('public.services') ? 'public-nav__link--active' : '' }}">Services</a>
                <a href="{{ route('public.stalls') }}" class="public-nav__link {{ request()->routeIs('public.stalls') ? 'public-nav__link--active' : '' }}">Vacant Stalls</a>
                <a href="{{ route('login') }}" class="public-nav__link">Login</a>
                <a href="{{ route('register') }}" class="public-nav__link public-nav__link--primary">Apply Now</a>
            </nav>
        </div>
    </header>

    @yield('public-content')

    <footer class="public-footer">
        <div class="public-container">
            <span>Maramag Fish Landing Management System</span>
            <<span>2025 <span class="font-bold text-blue-600">JJI Devz</span>, All rights reserved.</span>
        </div>
    </footer>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('[data-public-menu-toggle]');
    const menu = document.querySelector('[data-public-menu]');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', function () {
        const isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
</script>
@endsection
