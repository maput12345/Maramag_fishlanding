@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<style>
    .login-shell,
    .login-stage {
        background:
            radial-gradient(circle at 18% 16%, rgba(29, 78, 216, 0.14), transparent 24rem),
            linear-gradient(180deg, rgba(7, 21, 37, 0.88), rgba(7, 21, 37, 0.84)),
            url("{{ asset('image/background.webp') }}?v={{ filemtime(public_path('image/background.webp')) }}") center / cover fixed no-repeat !important;
    }

    .login-stage {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .verify-card {
        width: 100%;
        max-width: 520px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1.5rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 70px rgba(2, 6, 23, 0.32);
        overflow: hidden;
    }

    .verify-card__header {
        padding: 2rem 2rem 1.25rem;
        text-align: center;
    }

    .verify-card__logo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 7rem;
        height: 7rem;
        margin: 0 auto 1rem;
    }

    .verify-card__logo img {
        width: 7rem;
        height: 7rem;
        object-fit: contain;
    }

    .verify-card__title {
        color: #0f172a;
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .verify-card__subtitle {
        margin-top: 0.75rem;
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.65;
    }

    .verify-card__email {
        margin: 1.25rem 2rem 0;
        border-radius: 1rem;
        background: #f8fafc;
        padding: 0.875rem 1rem;
        text-align: center;
        color: #334155;
        font-size: 0.9rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .verify-card__body {
        padding: 1.5rem 2rem 2rem;
    }

    .verify-alert {
        margin-bottom: 1rem;
        border-radius: 1rem;
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        padding: 0.875rem 1rem;
        color: #166534;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .verify-actions {
        display: grid;
        gap: 0.75rem;
    }

    .verify-secondary {
        width: 100%;
        border-radius: 0.875rem;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        padding: 0.875rem 1rem;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 800;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .verify-secondary:hover {
        border-color: #94a3b8;
        background: #f8fafc;
        color: #0f172a;
    }

    .verify-field {
        display: grid;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .verify-label {
        color: #334155;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .verify-input {
        width: 100%;
        border-radius: 0.875rem;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        padding: 0.875rem 1rem;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .verify-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
        outline: none;
    }

    .verify-error {
        color: #b91c1c;
        font-size: 0.84rem;
        font-weight: 700;
    }

    @media (max-width: 640px) {
        .login-stage {
            padding: 1rem;
        }

        .verify-card__header,
        .verify-card__body {
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }

        .verify-card__email {
            margin-left: 1.25rem;
            margin-right: 1.25rem;
        }
    }
</style>

<div class="login-stage">
    <section class="verify-card" aria-labelledby="verify-email-title">
        <div class="verify-card__header">
            <div class="verify-card__logo" aria-hidden="true">
                <img src="{{ asset('image/logo-small.png') }}" alt="">
            </div>

            <h1 id="verify-email-title" class="verify-card__title">Verify your email</h1>
            <p class="verify-card__subtitle">
                We sent a verification link to your email. Open that message and confirm your account before continuing to the application portal.
            </p>
        </div>

        @auth
            <div class="verify-card__email">{{ auth()->user()->email }}</div>
        @endauth

        <div class="verify-card__body">
            @if (session('resent'))
                <div class="verify-alert" role="alert">
                    A fresh verification link has been sent to your email address.
                </div>
            @endif

            @if (session('status'))
                <div class="verify-alert" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if($editingEmail ?? false)
                <form method="POST" action="{{ route('verification.email.update') }}" class="verify-actions">
                    @csrf
                    @method('PATCH')

                    <div class="verify-field">
                        <label for="email" class="verify-label">Email Address</label>
                        <input id="email"
                               type="email"
                               name="email"
                               value="{{ old('email', auth()->user()->email) }}"
                               class="verify-input"
                               required
                               autocomplete="email"
                               autofocus>
                        @error('email')
                            <p class="verify-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="login-submit">
                        Update Email & Send Verification
                    </button>

                    <a href="{{ route('verification.notice') }}" class="verify-secondary">
                        Back to Verification
                    </a>
                </form>
            @else
                <div class="verify-actions">
                    <form method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="login-submit">
                            Resend Verification Email
                        </button>
                    </form>

                    <a href="{{ route('verification.email.edit') }}" class="verify-secondary">
                        Use Another Email
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="verify-secondary">
                            Back to Login
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </section>
</div>

<script>
    (() => {
        let leftVerificationPage = false;

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                leftVerificationPage = true;
                return;
            }

            if (leftVerificationPage) {
                window.location.reload();
            }
        });

        window.addEventListener('focus', () => {
            if (leftVerificationPage) {
                window.location.reload();
            }
        });
    })();
</script>
@endsection
