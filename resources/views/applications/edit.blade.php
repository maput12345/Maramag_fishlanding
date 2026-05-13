@extends('layouts.applicant')

@section('content')
@php
    $applicant = auth()->user();
@endphp

<div class="portal-page">
    <div class="portal-stage portal-stage--form">
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
                            Update only what LEEO requested. Your saved account profile will be used as the applicant name on this resubmission.
                        </p>
                    </div>
                    <x-status-badge status="Needs Revision" />
                </div>

                @if($application->remarks)
                    <div class="portal-inline-alert portal-inline-alert--warning">
                        LEEO remarks: {{ $application->remarks }}
                    </div>
                @endif

                <div class="portal-form-grid">
                    <div class="portal-field portal-field--wide">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Applicant Name</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $applicant->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">This comes from your account profile. Use the Profile button above if it needs correction before resubmitting.</p>
                        </div>
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
                            Upload at least one replacement file before resubmitting. Verified requirements stay locked.
                        </p>
                    </div>
                    <span class="portal-count-pill">{{ $application->requirements->count() }} records</span>
                </div>

                <div class="portal-inline-alert portal-inline-alert--warning">
                    A revision must include a new replacement file for a requirement marked Action Required.
                </div>

                <div class="portal-requirement-grid">
                    @foreach($application->requirements as $requirement)
                        @php
                            $requirementKey = 'requirements.' . $requirement->id;
                            $isVerified = strcasecmp((string) $requirement->verification_status, 'Verified') === 0;
                            $requiresApplicantAction = in_array((string) $requirement->verification_status, ['Needs Revision', 'Rejected'], true);
                        @endphp

                        <article class="portal-requirement-card {{ $requiresApplicantAction ? 'border-orange-200 bg-orange-50/40' : '' }}">
                            <div class="portal-requirement-card__header">
                                <div>
                                    <div class="portal-requirement-card__badges">
                                        <x-status-badge :status="$requirement->verification_status" />
                                        @if($requiresApplicantAction)
                                            <span class="portal-status-badge portal-status-badge--warning">Action Required</span>
                                        @endif
                                    </div>
                                    <h3 class="portal-requirement-card__title">{{ $requirement->display_name }}</h3>
                                    @if($requirement->is_additional && $requirement->custom_description)
                                        <p class="portal-requirement-card__description">{{ $requirement->custom_description }}</p>
                                    @endif
                                </div>

                                @if($requirement->file_url)
                                    <a href="{{ $requirement->file_url }}" target="_blank" rel="noopener" class="portal-button portal-button--secondary">
                                        <span>View Current File</span>
                                    </a>
                                @endif
                            </div>

                            <input type="hidden" name="requirements[{{ $requirement->id }}][id]" value="{{ $requirement->id }}">

                            @if($requiresApplicantAction)
                                @if($requirement->remarks)
                                    <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-4 text-base text-orange-700">
                                        <div class="flex items-start gap-3">
                                            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 flex-shrink-0 text-orange-600" />
                                            <div>
                                                <p class="font-semibold text-orange-800">Admin Note</p>
                                                <p class="mt-1">{{ $requirement->remarks }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="portal-form-grid portal-form-grid--requirement mt-4">
                                    <div class="portal-field portal-field--wide">
                                        <label for="requirements_{{ $requirement->id }}_file" class="portal-field__label">Replacement File</label>
                                        <input id="requirements_{{ $requirement->id }}_file"
                                               name="requirements[{{ $requirement->id }}][file]"
                                               type="file"
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               class="portal-input portal-input--file @error($requirementKey . '.file') portal-input--error @enderror">
                                        <p class="portal-field__hint">Upload a corrected PDF, JPG, or PNG file before resubmitting.</p>
                                        @error($requirementKey . '.file')
                                            <p class="portal-field__error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @elseif($requirement->remarks)
                                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-base text-slate-600">
                                    {{ $requirement->remarks }}
                                </div>
                            @endif
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
