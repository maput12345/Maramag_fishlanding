@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<div class="login-stage">
    <section class="login-hero">
        <div class="brand-chip">Password Recovery</div>
        <p class="login-kicker">Secure Access</p>
        <h1>Request a password reset link.</h1>
        <p class="login-description">Enter the email address tied to your account and we will send a secure link so you can restore access to the system.</p>
    </section>

    <section class="login-panel-wrap">
        <div class="login-card">
            <div class="login-card-header">
                <h2>Reset Password</h2>
                <p>We will send a password reset link to your registered email address.</p>
            </div>

            @if (session('status'))
                <div class="login-alert success">
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <form class="space-y-5" method="POST" action="{{ route('password.email') }}">
                @csrf

                <div>
                    <label for="email" class="login-label">Email Address</label>
                    <input id="email" type="email" class="login-input @error('email') border-red-300 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="login-submit">
                    Send Password Reset Link
                </button>
            </form>
        </div>
    </section>
</div>
@endsection
