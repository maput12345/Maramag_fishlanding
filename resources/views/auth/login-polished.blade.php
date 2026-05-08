@extends('layouts.app')

@section('body-class', 'login-shell login-shell--split theme-admin')

@section('content')
@php
    use App\Models\RequirementType;

    $audienceLabels = [
        RequirementType::APPLICANT_TYPE_BOTH => 'All applicants',
        RequirementType::APPLICANT_TYPE_NATURAL => 'Natural person',
        RequirementType::APPLICANT_TYPE_JURIDICAL => 'Juridical person',
    ];

    $previewOpenings = $applicationOpenings ?? collect();
    $fallbackRequirementTypes = $defaultRequirementTypes ?? collect();
    $previewRequirements = $previewOpenings
        ->flatMap(fn ($opening) => $opening->requirementTypes->isNotEmpty() ? $opening->requirementTypes : $fallbackRequirementTypes)
        ->unique('requirement_name')
        ->values();

    if ($previewRequirements->isEmpty()) {
        $previewRequirements = $fallbackRequirementTypes;
    }
@endphp

<div class="login-stage login-stage--split" x-data="{ previewOpen: false }">
    <div class="login-showcase-card">
        <section class="login-form-panel">


            <div class="login-form-header">
                <h1>Log In</h1>
                <p>Log in with your email and password.</p>
            </div>

            @if(session('message'))
                <div class="login-alert success">
                    <p>{{ session('message') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="login-alert error">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <form class="login-form-body" method="POST" action="{{ route('login') }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="email" class="login-label">Email address</label>
                        <input id="email"
                               name="email"
                               type="email"
                               autocomplete="email"
                               required
                               autofocus
                               value="{{ old('email') }}"
                               class="login-input split-login-input @error('email') border-red-300 @enderror"
                               placeholder="username@example.com">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="login-label">Password</label>
                        <input id="password"
                               name="password"
                               type="password"
                               autocomplete="current-password"
                               required
                               class="login-input split-login-input @error('password') border-red-300 @enderror"
                               placeholder="Enter your password">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="login-form-options">
                    <label for="remember" class="login-check-label">
                        <input id="remember"
                               name="remember"
                               type="checkbox"
                               {{ old('remember') ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="login-forgot-link">
                            Forgot your password?
                        </a>
                    @endif
                </div>

                <button type="submit" class="login-submit login-submit--split">
                    Log In
                </button>
            </form>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-700 shadow-sm">
                <p class="font-semibold text-slate-900">Looking for a vacant stall?</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Check current openings and prepare the required documents before applying.</p>

                <div class="mt-4 flex flex-col items-stretch gap-2">
                    <button type="button"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800"
                            @click="previewOpen = true">
                        View Stalls and Requirements
                    </button>

                    @if($hasAvailableStall ?? false)
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">
                            Apply here!
                        </a>
                    @else
                        <span role="button"
                              aria-disabled="true"
                              class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            No vacant stall available
                        </span>
                    @endif
                </div>
            </div>

            <p class="login-support-note">Need access? Please contact LEEO administrator LocalEconomicEnterpriseOffice@gmail.com.</p>
        </section>

        <aside class="login-welcome-panel">
            <div class="login-welcome-accent"></div>
            <div class="login-welcome-content login-welcome-content--brand">
                <div class="login-welcome-brand-mark">
                    <img src="{{ asset('image/logo-small.png') }}" alt="Maanyag Maramag logo" class="login-welcome-logo object-contain">
                </div>
                <h2>MAANYAG MARAMAG!</h2>
            </div>
        </aside>
    </div>

    <div class="workspace-popup"
         x-cloak
         x-show="previewOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="previewOpen = false"
         role="dialog"
         aria-modal="true"
         aria-labelledby="application-preview-title">
        <div class="workspace-popup__stage">
            <button type="button" class="workspace-popup__backdrop" aria-label="Close application preview" @click="previewOpen = false"></button>

            <section class="workspace-popup__panel workspace-popup__panel--lg">
                <header class="workspace-popup__header">
                    <div>
                        <h2 id="application-preview-title" class="workspace-popup__title">Available Stalls and Requirements</h2>
                        <p class="workspace-popup__subtitle">Review the current openings and documents before creating an applicant account.</p>
                    </div>
                    <button type="button" class="workspace-popup__close" aria-label="Close application preview" @click="previewOpen = false">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </header>

                <div class="workspace-popup__body workspace-popup__body--soft">
                    <div class="grid gap-4 md:grid-cols-2">
                        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Available Stalls</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Open for application</h3>
                                </div>
                                <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    {{ $previewOpenings->count() }} open
                                </span>
                            </div>

                            <div class="mt-4 space-y-3">
                                @forelse($previewOpenings as $opening)
                                    <article class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="text-sm font-semibold text-slate-900">{{ $opening->stall?->display_name ?? 'Available Stall' }}</h4>
                                                    <x-status-badge status="Open for Application" size="sm" />
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500">
                                                    Application period:
                                                    <span class="font-medium text-slate-700">{{ optional($opening->start_date)->format('M d, Y') }}</span>
                                                    to
                                                    <span class="font-medium text-slate-700">{{ optional($opening->end_date)->format('M d, Y') }}</span>
                                                </p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    Bidding date:
                                                    <span class="font-medium text-slate-700">{{ optional($opening->bidding_date)->format('M d, Y') ?? 'To be announced' }}</span>
                                                </p>
                                            </div>
                                            <div class="rounded-lg bg-white px-3 py-2 text-right">
                                                <p class="text-xs text-slate-500">Applicants</p>
                                                <p class="text-sm font-semibold text-slate-900">{{ number_format($opening->broker_applications_count ?? 0) }}</p>
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                        No stall is currently open for applications.
                                    </div>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Application Requirements</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Prepare these documents</h3>
                                </div>
                                <span class="shrink-0 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ $previewRequirements->count() }} items
                                </span>
                            </div>

                            <div class="mt-4 space-y-2">
                                @forelse($previewRequirements as $requirementType)
                                    @php
                                        $audience = $requirementType->pivot?->audience
                                            ?? $requirementType->audience
                                            ?? RequirementType::APPLICANT_TYPE_BOTH;
                                        $isRequired = (bool) ($requirementType->pivot?->is_required
                                            ?? $requirementType->is_required
                                            ?? true);
                                    @endphp

                                    <article class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <p class="text-sm font-medium text-slate-900">{{ $requirementType->requirement_name }}</p>
                                            <div class="flex flex-wrap gap-1.5">
                                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                                    {{ $audienceLabels[$audience] ?? 'All applicants' }}
                                                </span>
                                                @if($isRequired)
                                                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">Required</span>
                                                @else
                                                    <span class="rounded-full bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-500">Optional</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($requirementType->description)
                                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $requirementType->description }}</p>
                                        @endif
                                    </article>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                        Requirements will be shown once an application opening is available.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
