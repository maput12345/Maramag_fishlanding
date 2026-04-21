@extends('layouts.app')

@php
    $defaultApplicantType = old('applicant_type', array_key_first(\App\Models\RequirementType::applicantTypeOptions()));
    $applicantTypeOptions = \App\Models\RequirementType::applicantTypeOptions();
    $sharedApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_BOTH;
    $selectedApplicantType = old('applicant_type', $defaultApplicantType);
    $visibleRequirementCount = collect($requirementDefinitions)
        ->filter(fn (array $definition) => in_array($definition['audience'], [$selectedApplicantType, $sharedApplicantType], true))
        ->count();
@endphp

@section('body-class', 'portal-shell theme-admin')

@section('content')
<div class="portal-page">
    <div class="portal-stage portal-stage--form">
        <div class="portal-topbar">
            <div class="portal-topbar__brand">
                <span class="portal-brand-pill">LEEO Digital Services</span>
                <div>
                    <p class="portal-topbar__title">Broker Application Portal</p>
                    <p class="portal-topbar__meta">A polished follow-through from sign-in to document submission.</p>
                </div>
            </div>

            <div class="portal-topbar__controls">
                <a href="{{ route('applications.index') }}" class="portal-button portal-button--secondary">
                    <span>Back to Portal</span>
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
              action="{{ route('applications.store', $opening) }}"
              enctype="multipart/form-data"
              class="portal-form-shell">
            @csrf

            @if($errors->any())
                <div class="portal-alert-stack">
                    <div class="portal-alert portal-alert--error">
                        Please review the highlighted fields before submitting your broker application.
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="portal-form-errors" role="alert" aria-live="polite">
                    <h2 class="portal-form-errors__title">Please fix these items before submitting</h2>
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
                        <p class="portal-section-card__eyebrow">Application Form</p>
                        <h2 class="portal-section-card__title">Applicant Details</h2>
                        <p class="portal-section-card__description">
                            Complete the broker application for {{ $opening->stall?->display_name ?? 'this stall opening' }}. Provide the personal or representative information that will appear on your submission.
                        </p>
                    </div>
                    <span class="portal-count-pill">Step 1 of 2</span>
                </div>

                <div class="portal-form-grid">
                    <div class="portal-field">
                        <div class="portal-field__label-row">
                            <label for="applicant_type" class="portal-field__label">Applicant Type</label>
                            <span class="portal-field__badge">Checklist filter</span>
                        </div>
                        <select id="applicant_type"
                                name="applicant_type"
                                class="portal-input portal-select @error('applicant_type') portal-input--error @enderror">
                            @foreach($applicantTypeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('applicant_type', $selectedApplicantType) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="portal-field__hint">Choose whether the bidder is a natural person or a juridical person so the correct checklist is shown.</p>
                        @error('applicant_type')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="first_name" class="portal-field__label">First Name</label>
                        <input id="first_name"
                               name="first_name"
                               type="text"
                               value="{{ old('first_name') }}"
                               class="portal-input @error('first_name') portal-input--error @enderror"
                               placeholder="Enter first name">
                        @error('first_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="middle_name" class="portal-field__label">Middle Name</label>
                        <input id="middle_name"
                               name="middle_name"
                               type="text"
                               value="{{ old('middle_name') }}"
                               class="portal-input @error('middle_name') portal-input--error @enderror"
                               placeholder="Enter middle name">
                        @error('middle_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="last_name" class="portal-field__label">Last Name</label>
                        <input id="last_name"
                               name="last_name"
                               type="text"
                               value="{{ old('last_name') }}"
                               class="portal-input @error('last_name') portal-input--error @enderror"
                               placeholder="Enter last name">
                        @error('last_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="suffix" class="portal-field__label">Suffix</label>
                        <input id="suffix"
                               name="suffix"
                               type="text"
                               value="{{ old('suffix') }}"
                               class="portal-input @error('suffix') portal-input--error @enderror"
                               placeholder="Jr., Sr., III">
                        @error('suffix')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide">
                        <div class="portal-field__label-row">
                            <label for="business_name" class="portal-field__label">Business / Entity Name</label>
                            <span class="portal-field__badge" data-business-badge>Optional for natural persons</span>
                        </div>
                        <input id="business_name"
                               name="business_name"
                               type="text"
                               value="{{ old('business_name') }}"
                               class="portal-input @error('business_name') portal-input--error @enderror"
                               placeholder="Enter business or entity name"
                               data-business-input>
                        <p class="portal-field__hint" data-business-hint>Required for juridical persons such as corporations or partnerships.</p>
                        @error('business_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field">
                        <label for="contact_number" class="portal-field__label">Contact Number</label>
                        <input id="contact_number"
                               name="contact_number"
                               type="text"
                               value="{{ old('contact_number') }}"
                               class="portal-input @error('contact_number') portal-input--error @enderror"
                               placeholder="09xx xxx xxxx">
                        @error('contact_number')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide">
                        <label for="address" class="portal-field__label">Address</label>
                        <textarea id="address"
                                  name="address"
                                  rows="4"
                                  class="portal-input portal-textarea @error('address') portal-input--error @enderror"
                                  placeholder="Enter complete address">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="portal-section-card portal-section-card--form">
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Document Checklist</p>
                        <h2 class="portal-section-card__title">Upload Requirements</h2>
                        <p class="portal-section-card__description">
                            Only the checklist for your selected applicant type is shown. Shared requirements are marked for all applicants. Accepted files: PDF, JPG, JPEG, and PNG up to 5MB each.
                        </p>
                    </div>
                    <span class="portal-count-pill" data-checklist-count-pill>{{ $visibleRequirementCount }} requirements shown</span>
                </div>

                <div class="portal-requirement-grid">
                    @foreach($requirementTypes as $requirementType)
                        @php
                            $definition = $requirementDefinitions[$requirementType->requirement_name] ?? [
                                'audience' => $sharedApplicantType,
                                'is_required' => (bool) $requirementType->is_required,
                                'description' => $requirementType->description,
                            ];
                            $audience = $definition['audience'] ?? $sharedApplicantType;
                            $isRequired = (bool) ($definition['is_required'] ?? false);
                            $isVisible = in_array($audience, [$selectedApplicantType, $sharedApplicantType], true);
                        @endphp

                        <article class="portal-requirement-card"
                                 data-requirement-item
                                 data-audience="{{ $audience }}"
                                 data-required="{{ $isRequired ? 'true' : 'false' }}"
                                 @if(!$isVisible) hidden @endif>
                            <div class="portal-requirement-card__header">
                                <div>
                                    <div class="portal-requirement-card__badges">
                                        <span class="portal-status-badge portal-status-badge--neutral">
                                            {{ \App\Models\RequirementType::applicantTypeLabel($audience) }}
                                        </span>
                                        <span class="portal-status-badge {{ $isRequired ? 'portal-status-badge--warning' : 'portal-status-badge--success' }}">
                                            {{ $isRequired ? 'Required' : 'Optional' }}
                                        </span>
                                    </div>

                                    <h3 class="portal-requirement-card__title">{{ $requirementType->requirement_name }}</h3>
                                    <p class="portal-requirement-card__description">
                                        {{ $definition['description'] ?? $requirementType->description ?? 'Upload the official supporting document for this checklist item.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="portal-form-grid portal-form-grid--requirement">
                                <div class="portal-field portal-field--wide">
                                    <label for="requirements_{{ $requirementType->id }}_file" class="portal-field__label">Document File</label>
                                    <input id="requirements_{{ $requirementType->id }}_file"
                                           name="requirements[{{ $requirementType->id }}][file]"
                                           type="file"
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           class="portal-input portal-input--file @error('requirements.' . $requirementType->id . '.file') portal-input--error @enderror"
                                           data-requirement-input
                                           @disabled(!$isVisible)
                                           @required($isVisible && $isRequired)>
                                    <p class="portal-field__hint">Upload one clear file for this requirement. Maximum file size is 5MB.</p>
                                    @error('requirements.' . $requirementType->id . '.file')
                                        <p class="portal-field__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="portal-field">
                                    <label for="requirements_{{ $requirementType->id }}_document_number" class="portal-field__label">Document Number</label>
                                    <input id="requirements_{{ $requirementType->id }}_document_number"
                                           name="requirements[{{ $requirementType->id }}][document_number]"
                                           type="text"
                                           value="{{ old('requirements.' . $requirementType->id . '.document_number') }}"
                                           class="portal-input @error('requirements.' . $requirementType->id . '.document_number') portal-input--error @enderror"
                                           placeholder="Enter reference number"
                                           data-requirement-input
                                           @disabled(!$isVisible)>
                                    @error('requirements.' . $requirementType->id . '.document_number')
                                        <p class="portal-field__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="portal-field">
                                    <label for="requirements_{{ $requirementType->id }}_issuing_office" class="portal-field__label">Issuing Office</label>
                                    <input id="requirements_{{ $requirementType->id }}_issuing_office"
                                           name="requirements[{{ $requirementType->id }}][issuing_office]"
                                           type="text"
                                           value="{{ old('requirements.' . $requirementType->id . '.issuing_office') }}"
                                           class="portal-input @error('requirements.' . $requirementType->id . '.issuing_office') portal-input--error @enderror"
                                           placeholder="Enter issuing office"
                                           data-requirement-input
                                           @disabled(!$isVisible)>
                                    @error('requirements.' . $requirementType->id . '.issuing_office')
                                        <p class="portal-field__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="portal-field">
                                    <label for="requirements_{{ $requirementType->id }}_issue_date" class="portal-field__label">Issue Date</label>
                                    <input id="requirements_{{ $requirementType->id }}_issue_date"
                                           name="requirements[{{ $requirementType->id }}][issue_date]"
                                           type="date"
                                           value="{{ old('requirements.' . $requirementType->id . '.issue_date') }}"
                                           class="portal-input @error('requirements.' . $requirementType->id . '.issue_date') portal-input--error @enderror"
                                           data-requirement-input
                                           @disabled(!$isVisible)>
                                    @error('requirements.' . $requirementType->id . '.issue_date')
                                        <p class="portal-field__error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="portal-field">
                                    <label for="requirements_{{ $requirementType->id }}_expiry_date" class="portal-field__label">Expiry Date</label>
                                    <input id="requirements_{{ $requirementType->id }}_expiry_date"
                                           name="requirements[{{ $requirementType->id }}][expiry_date]"
                                           type="date"
                                           value="{{ old('requirements.' . $requirementType->id . '.expiry_date') }}"
                                           class="portal-input @error('requirements.' . $requirementType->id . '.expiry_date') portal-input--error @enderror"
                                           data-requirement-input
                                           @disabled(!$isVisible)>
                                    @error('requirements.' . $requirementType->id . '.expiry_date')
                                        <p class="portal-field__error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="portal-form-actions">
                <a href="{{ route('applications.index') }}" class="portal-button portal-button--ghost">
                    <span>Cancel</span>
                </a>

                <button type="submit" class="portal-button portal-button--primary portal-button--cta">
                    <span>Submit Application</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const applicantTypeSelect = document.getElementById('applicant_type');

    if (!applicantTypeSelect) {
        return;
    }

    const sharedApplicantType = @json($sharedApplicantType);
    const requirementItems = document.querySelectorAll('[data-requirement-item]');
    const businessInput = document.querySelector('[data-business-input]');
    const businessBadge = document.querySelector('[data-business-badge]');
    const businessHint = document.querySelector('[data-business-hint]');
    const checklistCountPill = document.querySelector('[data-checklist-count-pill]');
    const juridicalType = @json(\App\Models\RequirementType::APPLICANT_TYPE_JURIDICAL);

    const updateRequirementVisibility = () => {
        const activeApplicantType = applicantTypeSelect.value;
        let visibleCount = 0;

        requirementItems.forEach((item) => {
            const audience = item.dataset.audience;
            const isRequired = item.dataset.required === 'true';
            const isVisible = audience === activeApplicantType || audience === sharedApplicantType;

            item.hidden = !isVisible;

            item.querySelectorAll('[data-requirement-input]').forEach((input) => {
                input.disabled = !isVisible;

                if (input.type === 'file') {
                    input.required = isVisible && isRequired;
                }
            });

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (checklistCountPill) {
            checklistCountPill.textContent = `${visibleCount} requirement${visibleCount === 1 ? '' : 's'} shown`;
        }

        const isJuridical = activeApplicantType === juridicalType;

        if (businessInput) {
            businessInput.required = isJuridical;
        }

        if (businessBadge) {
            businessBadge.textContent = isJuridical ? 'Required for juridical persons' : 'Optional for natural persons';
        }

        if (businessHint) {
            businessHint.textContent = isJuridical
                ? 'Enter the registered corporation, partnership, or entity name.'
                : 'Leave this blank unless you are applying on behalf of an entity.';
        }
    };

    applicantTypeSelect.addEventListener('change', updateRequirementVisibility);
    updateRequirementVisibility();
});
</script>
@endsection
