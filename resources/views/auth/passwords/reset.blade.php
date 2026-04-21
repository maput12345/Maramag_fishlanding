@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<div class="login-stage">
    <section class="login-hero">
        <div class="brand-chip">Password Recovery</div>
        <p class="login-kicker">New Credentials</p>
        <h1>Choose a new password for your account.</h1>
        <p class="login-description">Set a strong new password to restore access while keeping your account secure.</p>
    </section>

    <section class="login-panel-wrap">
        <div class="login-card">
            <div class="login-card-header">
                <h2>Reset Password</h2>
                <p>Enter your email and a new password to complete the recovery process.</p>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="login-label">Email Address</label>
                    <input id="email" type="email" class="login-input @error('email') border-red-300 @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="login-label">Password</label>
                    <input id="password" type="password" class="login-input @error('password') border-red-300 @enderror" name="password" required autocomplete="new-password">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password-confirm" class="login-label">Confirm Password</label>
                    <input id="password-confirm" type="password" class="login-input" name="password_confirmation" required autocomplete="new-password">
                </div>

                <button type="submit" class="login-submit">
                    Reset Password
                </button>
            </form>
        </div>
    </section>
</div>
@endsection
