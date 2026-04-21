@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<div class="login-stage">
    <section class="login-hero">
        <div class="brand-chip">Account Access</div>
        <p class="login-kicker">New User Setup</p>
        <h1>Create a clean account for the operations workspace.</h1>
        <p class="login-description">Registration is available for controlled setup, but your main production flow should still be managed by LEEO staff through the user management module.</p>
    </section>

    <section class="login-panel-wrap">
        <div class="login-card">
            <div class="login-card-header">
                <h2>Create an applicant account</h2>
                <p>Register first, then you can submit a broker application whenever LEEO opens a vacant stall.</p>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('register') }}">
                @csrf

                <div>
                    <label for="name" class="login-label">Name</label>
                    <input id="name" type="text" class="login-input @error('name') border-red-300 @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="login-label">Email Address</label>
                    <input id="email" type="email" class="login-input @error('email') border-red-300 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
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
                    Create Applicant Account
                </button>
            </form>
        </div>
    </section>
</div>
@endsection
