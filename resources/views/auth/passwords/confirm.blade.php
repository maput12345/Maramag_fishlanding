@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<div class="login-stage">
    <section class="login-hero">
        <div class="brand-chip">Protected Action</div>
        <p class="login-kicker">Identity Check</p>
        <h1>Confirm your password before continuing.</h1>
        <p class="login-description">This extra step protects sensitive actions in the system by making sure the current user is verified.</p>
    </section>

    <section class="login-panel-wrap">
        <div class="login-card">
            <div class="login-card-header">
                <h2>Confirm Password</h2>
                <p>Please confirm your password before continuing.</p>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div>
                    <label for="password" class="login-label">Password</label>
                    <input id="password" type="password" class="login-input @error('password') border-red-300 @enderror" name="password" required autocomplete="current-password">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="login-submit">
                    Confirm Password
                </button>

                @if (Route::has('password.request'))
                    <a class="block text-center text-sm font-medium text-blue-600 transition-colors hover:text-blue-500" href="{{ route('password.request') }}">
                        Forgot your password?
                    </a>
                @endif
            </form>
        </div>
    </section>
</div>
@endsection
