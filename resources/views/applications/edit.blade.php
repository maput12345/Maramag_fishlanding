@extends('layouts.app')

@section('body-class', 'portal-shell theme-admin')

@section('content')
<div class="portal-page">
    <div class="portal-stage portal-stage--form">
        <div class="portal-topbar">
            <div class="portal-topbar__brand">
                <span class="portal-brand-pill">LEEO Digital Services</span>
                <div>
                    <p class="portal-topbar__title">Broker Application Portal</p>
                    <p class="portal-topbar__meta">Revise the details or documents requested by LEEO.</p>
                </div>
            </div>

            <div class="portal-topbar__controls">
                <a href="{{ route('applications.show', $application) }}" class="portal-button portal-button--secondary">
                    <span>Back to Details</span>
                </a>

                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="portal-button portal-button--ghost">
                    <x-heroicon-o-arrow-right-on-rectangle class="portal-button__icon" />
                    <span>Logout</span>
                </a>
            </div>

            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>
        </div>

        <form method="POST"
              action="{{ route('applications.update', $application) }}"
              enctype="multipart/form-data"
              class="portal-form-shell">
            @csrf
            @method('PATCH')

            @if($errors->any())
                <div class="portal-form-errors" role="alert" aria-live="polite">
                    <h2 class="portal-form-errors__title">Please fix these items before resubmitting</h2>
                    <ul class="portal-form-errors__list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="portal-section-card portal-section-card--form">
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Revision Form</p>
                        <h1 class="portal-section-card__title">Resubmit Application</h1>
                        <p class="portal-section-card__description">
                            Update only what LEEO requested. After resubmission, the application returns to Submitted for another review.
                        </p>
                    </div>
                    <span class="portal-status-badge portal-status-badge--warning">Needs Revision</span>
                </div>

                @if($application->remarks)
                    <div class="portal-inline-alert portal-inline-alert--warning">
                        LEEO remarks: {{ $application->remarks }}
                    </div>
                @endif

                <div class="portal-form-grid">
                    <div class="portal-field">
                        <label for="first_name" class="portal-field__label">First Name</label>
                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $application->first_name) }}" class="portal-input @error('first_name') portal-input--error @enderror">
                        @error('first_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="middle_name" class="portal-field__label">Middle Name</label>
                        <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $application->middle_name) }}" class="portal-input @error('middle_name') portal-input--error @enderror">
                        @error('middle_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="last_name" class="portal-field__label">Last Name</label>
                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $application->last_name) }}" class="portal-input @error('last_name') portal-input--error @enderror">
                        @error('last_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="suffix" class="portal-field__label">Suffix</label>
                        <input id="suffix" name="suffix" type="text" value="{{ old('suffix', $application->suffix) }}" class="portal-input @error('suffix') portal-input--error @enderror">
                        @error('suffix')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide">
                        <label for="business_name" class="portal-field__label">Business / Entity Name</label>
                        <input id="business_name" name="business_name" type="text" value="{{ old('business_name', $application->business_name) }}" class="portal-input @error('business_name') portal-input--error @enderror">
                        @error('business_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="contact_number" class="portal-field__label">Contact Number</label>
                        <input id="contact_number" name="contact_number" type="text" value="{{ old('contact_number', $application->contact_number) }}" class="portal-input @error('contact_number') portal-input--error @enderror">
                        @error('contact_number')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide">
                        <label for="address" class="portal-field__label">Address</label>
                        <textarea id="address" name="address" rows="4" class="portal-input portal-textarea @error('address') portal-input--error @enderror">{{ old('address', $application->address) }}</textarea>
                        @error('address')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="portal-section-card portal-section-card--form">
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Document Revision</p>
                        <h2 class="portal-section-card__title">Submitted Requirements</h2>
                        <p class="portal-section-card__description">
                            Upload a replacement file only when LEEO requested a correction. Existing files remain attached if no replacement is selected.
                        </p>
                    </div>
                    <span class="portal-count-pill">{{ $application->requirements->count() }} records</span>
                </div>

                <div class="portal-requirement-grid">
                    @foreach($application->requirements as $requirement)
                        @php
                            $verificationTone = match ($requirement->verification_status) {
                                'Verified' => 'portal-status-badge--success',
                                'Rejected' => 'portal-status-badge--danger',
                                default => 'portal-status-badge--neutral',
                            };
                            $requirementKey = 'requirements.' . $requirement->id;
                        @endphp

                        <article class="portal-requirement-card">
                            <div class="portal-requirement-card__header">
                                <div>
                                    <div class="portal-requirement-card__badges">
                                        <span class="portal-status-badge {{ $verificationTone }}">{{ $requirement->verification_status }}</span>
                                    </div>
                                    <h3 class="portal-requirement-card__title">{{ $requirement->requirementType?->requirement_name }}</h3>
                                    @if($requirement->remarks)
                                        <p class="portal-requirement-card__description">LEEO note: {{ $requirement->remarks }}</p>
                                    @endif
                                </div>

                                @if($requirement->file_url)
                                    <a href="{{ $requirement->file_url }}" target="_blank" rel="noopener" class="portal-button portal-button--secondary">
                                        <span>View Current File</span>
                                    </a>
                                @endif
                            </div>

                            <input type="hidden" name="requirements[{{ $requirement->id }}][id]" value="{{ $requirement->id }}">

                            <div class="portal-form-grid portal-form-grid--requirement">
                                <div class="portal-field portal-field--wide">
                                    <label for="requirements_{{ $requirement->id }}_file" class="portal-field__label">Replacement File</label>
                                    <input id="requirements_{{ $requirement->id }}_file"
                                           name="requirements[{{ $requirement->id }}][file]"
                                           type="file"
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           class="portal-input portal-input--file @error($requirementKey . '.file') portal-input--error @enderror">
                                    <p class="portal-field__hint">Leave blank to keep the current uploaded file.</p>
                                    @error($requirementKey . '.file')
                                        <p class="portal-field__error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="portal-form-actions">
                <a href="{{ route('applications.show', $application) }}" class="portal-button portal-button--ghost">
                    <span>Cancel</span>
                </a>

                <button type="submit" class="portal-button portal-button--primary portal-button--cta">
                    <span>Submit</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
