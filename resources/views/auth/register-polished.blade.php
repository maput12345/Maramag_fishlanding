@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<style>
    .login-shell,
    .login-stage {
        background:
            radial-gradient(circle at 18% 16%, rgba(29, 78, 216, 0.14), transparent 24rem),
            linear-gradient(180deg, rgba(7, 21, 37, 0.88), rgba(7, 21, 37, 0.84)),
            url("{{ asset('image/background.png') }}") center / cover fixed no-repeat !important;
    }

    .login-stage {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .login-panel-wrap {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-card {
        width: 100%;
        max-width: 550px;
    }

    .login-card,
    .login-hero {
        border-color: rgba(255, 255, 255, 0.18) !important;
        box-shadow: 0 24px 70px rgba(2, 6, 23, 0.32) !important;
    }
</style>

<div class="login-stage">
    <section class="login-panel-wrap">
        <div class="login-card">
            <div class="login-card-header">
                <h2>Create an applicant account</h2>
                <p>Register to apply for a broker stall at Maramag Fish Landing.</p>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('register') }}">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="login-label">First Name</label>
                        <input id="first_name" type="text" class="login-input @error('first_name') border-red-300 @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus>
                        @error('first_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="middle_name" class="login-label">Middle Name</label>
                        <input id="middle_name" type="text" class="login-input @error('middle_name') border-red-300 @enderror" name="middle_name" value="{{ old('middle_name') }}" autocomplete="additional-name">
                        @error('middle_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="login-label">Last Name</label>
                        <input id="last_name" type="text" class="login-input @error('last_name') border-red-300 @enderror" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
                        @error('last_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="suffix" class="login-label">Suffix</label>
                        <input id="suffix" type="text" class="login-input @error('suffix') border-red-300 @enderror" name="suffix" value="{{ old('suffix') }}" autocomplete="honorific-suffix" placeholder="Optional">
                        @error('suffix')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="login-label">Email Address</label>
                    <input id="email" type="email" class="login-input @error('email') border-red-300 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email-confirm" class="login-label">Confirm Email Address</label>
                    <input id="email-confirm" type="email" class="login-input" name="email_confirmation" value="{{ old('email_confirmation') }}" required autocomplete="email">
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
