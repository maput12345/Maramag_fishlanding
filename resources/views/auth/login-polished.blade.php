@extends('layouts.app')

@section('body-class', 'login-shell login-shell--split theme-admin')

@section('content')
<div class="login-stage login-stage--split">
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

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold text-slate-900">Looking for a vacant stall?</p>
                @if($hasAvailableStall ?? false)
                    <a href="{{ route('register') }}" class="mt-3 inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800">
                        Apply here!
                    </a>
                @else
                    <span role="button"
                          aria-disabled="true"
                          class="mt-3 inline-flex cursor-not-allowed items-center rounded-full bg-slate-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        No vacant stall available
                    </span>
                @endif
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
</div>
@endsection
