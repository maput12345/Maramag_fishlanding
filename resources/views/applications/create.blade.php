@extends('layouts.applicant')

@php
    $defaultApplicantType = old('applicant_type', array_key_first(\App\Models\RequirementType::applicantTypeOptions()));
    $applicantTypeOptions = \App\Models\RequirementType::applicantTypeOptions();
    $sharedApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_BOTH;
    $naturalApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_NATURAL;
    $juridicalApplicantType = \App\Models\RequirementType::APPLICANT_TYPE_JURIDICAL;
    $selectedApplicantType = old('applicant_type', $defaultApplicantType);
    $selectedIsNatural = $selectedApplicantType === $naturalApplicantType;
    $selectedIsJuridical = $selectedApplicantType === $juridicalApplicantType;
    $selectedIsMarriedNatural = $selectedIsNatural && old('civil_status') === 'Married';
    $hasRequirementErrors = collect($errors->getBag('default')->keys())
        ->contains(fn (string $key) => str_starts_with($key, 'requirements.'));
    $initialStep = $hasRequirementErrors ? 2 : 1;
    $visibleRequirementCount = collect($requirementDefinitions)
        ->filter(fn (array $definition) => in_array($definition['audience'], [$selectedApplicantType, $sharedApplicantType], true))
        ->count();
    $stall = $opening->stall;
    $stallGallery = $stall?->gallery_image_urls ?? [];
    $applicant = auth()->user();
    $applicantProfile = $applicantProfile ?? null;
    $latestApplication = $latestApplication ?? null;
    $profileDraftVersion = optional($applicantProfile?->updated_at)->timestamp ?? 'no-profile';
@endphp

@section('content')
<div class="portal-page">
    <div class="portal-stage portal-stage--form">
        <form method="POST"
              action="{{ route('applications.store', $opening) }}"
              enctype="multipart/form-data"
              class="portal-form-shell"
              @submit="if (currentStep === 1) { $event.preventDefault(); nextStep($root); }"
              x-data="{
                  currentStep: @js($initialStep),
                  applicantType: @js($selectedApplicantType),
                  civilStatus: @js(old('civil_status', '')),
                  naturalType: @js($naturalApplicantType),
                  juridicalType: @js($juridicalApplicantType),
                  goToStep(step) {
                      this.currentStep = step;
                      window.setTimeout(() => {
                          document.querySelector('[data-application-form-top]')?.scrollIntoView({
                              behavior: 'smooth',
                              block: 'start'
                          });
                      }, 0);
                  },
                  nextStep(form) {
                      const fields = Array.from(form.querySelectorAll('[data-step-one-input]'))
                          .filter((field) => !field.disabled);
                      const invalidField = fields.find((field) => !field.checkValidity());

                      if (invalidField) {
                          invalidField.reportValidity();
                          invalidField.focus({ preventScroll: false });
                          return;
                      }

                      this.goToStep(2);
                  }
              }"
              data-application-autosave-form
              data-autosave-key="broker-application-draft:{{ auth()->id() }}:{{ $opening->id }}:{{ $profileDraftVersion }}">
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

            <section class="portal-section-card portal-section-card--form" data-application-form-top>
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Apply for Stall</p>
                        <h1 class="portal-section-card__title">{{ $stall?->display_name ?? 'Open Stall Application' }}</h1>
                    </div>
                    <span class="portal-status-badge portal-status-badge--open">{{ $opening->opening_status }}</span>
                </div>

                @if(count($stallGallery) > 0)
                    <div class="stall-gallery-modal__grid">
                        @foreach($stallGallery as $galleryImage)
                            <a href="{{ $galleryImage }}" target="_blank" rel="noopener" class="stall-gallery-modal__image-link">
                                <img
                                    src="{{ $galleryImage }}"
                                    alt="{{ $stall?->display_name ?? 'Stall' }} photo {{ $loop->iteration }}"
                                    class="stall-gallery-modal__image"
                                >
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="portal-detail-list">
                    <div class="portal-detail-item">
                        <div class="portal-detail-item__icon">
                            <x-heroicon-o-calendar-days class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="portal-detail-item__label">Application Window</p>
                            <p class="portal-detail-item__value">
                                {{ optional($opening->start_date)->format('M d, Y') }} to {{ optional($opening->end_date)->format('M d, Y') }}
                            </p>
                        </div>
                    </div>

                    @if($opening->bidding_location)
                        <div class="portal-detail-item">
                            <div class="portal-detail-item__icon portal-detail-item__icon--gold">
                                <x-heroicon-o-map-pin class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="portal-detail-item__label">Bidding Location</p>
                                <p class="portal-detail-item__value">{{ $opening->bidding_location }}</p>
                            </div>
                        </div>
                    @endif

                    @if($stall?->address || $stall?->area_sqm)
                        <div class="portal-detail-item">
                            <div class="portal-detail-item__icon">
                                <x-heroicon-o-map-pin class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="portal-detail-item__label">Stall Location</p>
                                <p class="portal-detail-item__value">
                                    {{ $stall->address ?: 'No address recorded' }}
                                    @if($stall?->area_sqm)
                                        <span class="text-slate-400">/</span> {{ number_format((float) $stall->area_sqm, 2) }} sq m
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($stall?->remarks)
                        <div class="portal-detail-item">
                            <div class="portal-detail-item__icon">
                                <x-heroicon-o-document-text class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="portal-detail-item__label">Description</p>
                                <p class="portal-detail-item__value">{{ $stall->remarks }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="portal-section-card portal-section-card--form"
                     x-show="currentStep === 1"
                     x-cloak>
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Application Form</p>
                        <h2 class="portal-section-card__title">Applicant Details</h2>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <span class="portal-count-pill">Step 1 of 2</span>
                        <span class="portal-count-pill" data-autosave-status>Draft saved</span>
                    </div>
                </div>

                <div class="portal-form-grid">
                    <div class="portal-field">
                        <div class="portal-field__label-row">
                            <label for="applicant_type" class="portal-field__label">Applicant Type</label>
                            <span class="portal-field__badge">Checklist filter</span>
                        </div>
                        <select id="applicant_type"
                                name="applicant_type"
                                x-model="applicantType"
                                data-step-one-input
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

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="first_name" class="portal-field__label">First Name</label>
                        <input id="first_name"
                               name="first_name"
                               type="text"
                               value="{{ old('first_name', $applicantProfile?->first_name ?? $latestApplication?->first_name) }}"
                               class="portal-input @error('first_name') portal-input--error @enderror"
                               autocomplete="given-name"
                               data-applicant-type-input
                               data-step-one-input
                               data-profile-default
                               :disabled="applicantType !== naturalType"
                               :required="applicantType === naturalType">
                        @error('first_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="middle_name" class="portal-field__label">Middle Name</label>
                        <input id="middle_name"
                               name="middle_name"
                               type="text"
                               value="{{ old('middle_name', $applicantProfile?->middle_name ?? $latestApplication?->middle_name) }}"
                               class="portal-input @error('middle_name') portal-input--error @enderror"
                               autocomplete="additional-name"
                               data-applicant-type-input
                               data-step-one-input
                               data-profile-default
                               :disabled="applicantType !== naturalType">
                        @error('middle_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="last_name" class="portal-field__label">Last Name</label>
                        <input id="last_name"
                               name="last_name"
                               type="text"
                               value="{{ old('last_name', $applicantProfile?->last_name ?? $latestApplication?->last_name) }}"
                               class="portal-input @error('last_name') portal-input--error @enderror"
                               autocomplete="family-name"
                               data-applicant-type-input
                               data-step-one-input
                               data-profile-default
                               :disabled="applicantType !== naturalType"
                               :required="applicantType === naturalType">
                        @error('last_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="suffix" class="portal-field__label">Suffix</label>
                        <input id="suffix"
                               name="suffix"
                               type="text"
                               value="{{ old('suffix', $applicantProfile?->suffix ?? $latestApplication?->suffix) }}"
                               class="portal-input @error('suffix') portal-input--error @enderror"
                               autocomplete="honorific-suffix"
                               placeholder="Optional"
                               data-applicant-type-input
                               data-step-one-input
                               data-profile-default
                               :disabled="applicantType !== naturalType">
                        @error('suffix')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="contact_number" class="portal-field__label">Contact Number</label>
                        <input id="contact_number"
                               name="contact_number"
                               type="text"
                               value="{{ old('contact_number', $applicantProfile?->contact_number ?? $latestApplication?->contact_number) }}"
                               class="portal-input @error('contact_number') portal-input--error @enderror"
                               placeholder="09xx xxx xxxx"
                               data-applicant-type-input
                               data-step-one-input
                               data-profile-default
                               :disabled="applicantType !== naturalType"
                               :required="applicantType === naturalType">
                        @error('contact_number')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="address" class="portal-field__label">Address</label>
                        <textarea id="address"
                                  name="address"
                                  rows="4"
                                  class="portal-input portal-textarea @error('address') portal-input--error @enderror"
                                  placeholder="Enter complete address"
                                  data-applicant-type-input
                                  data-step-one-input
                                  data-profile-default
                                  :disabled="applicantType !== naturalType"
                                  :required="applicantType === naturalType">{{ old('address', $applicantProfile?->address ?? $latestApplication?->address) }}</textarea>
                        @error('address')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         x-show="applicantType === naturalType"
                         x-cloak>
                        <label for="civil_status" class="portal-field__label">Civil Status</label>
                        <select id="civil_status"
                                name="civil_status"
                                x-model="civilStatus"
                                class="portal-input portal-select @error('civil_status') portal-input--error @enderror"
                                data-applicant-type-input
                                data-civil-status-input
                                data-step-one-input
                                :disabled="applicantType !== naturalType"
                                :required="applicantType === naturalType">
                            <option value="">Select civil status</option>
                            @foreach(['Single', 'Married', 'Widowed', 'Separated'] as $civilStatus)
                                <option value="{{ $civilStatus }}" @selected(old('civil_status') === $civilStatus)>{{ $civilStatus }}</option>
                            @endforeach
                        </select>
                        @error('civil_status')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         data-married-section
                         x-show="applicantType === naturalType && civilStatus === 'Married'"
                         x-cloak>
                        <label for="spouse_name" class="portal-field__label">Spouse Name</label>
                        <input id="spouse_name"
                               name="spouse_name"
                               type="text"
                               value="{{ old('spouse_name') }}"
                               class="portal-input @error('spouse_name') portal-input--error @enderror"
                               placeholder="Enter spouse name"
                               data-applicant-type-input
                               data-married-input
                               data-step-one-input
                               :disabled="applicantType !== naturalType || civilStatus !== 'Married'"
                               :required="applicantType === naturalType && civilStatus === 'Married'">
                        @error('spouse_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $naturalApplicantType }}"
                         data-married-section
                         x-show="applicantType === naturalType && civilStatus === 'Married'"
                         x-cloak>
                        <label for="spouse_contact_number" class="portal-field__label">Spouse Contact Number</label>
                        <input id="spouse_contact_number"
                               name="spouse_contact_number"
                               type="text"
                               value="{{ old('spouse_contact_number') }}"
                               class="portal-input @error('spouse_contact_number') portal-input--error @enderror"
                               placeholder="09xx xxx xxxx"
                               data-applicant-type-input
                               data-married-input
                               data-step-one-input
                               :disabled="applicantType !== naturalType || civilStatus !== 'Married'">
                        @error('spouse_contact_number')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide"
                         data-applicant-type-section="{{ $juridicalApplicantType }}"
                         x-show="applicantType === juridicalType"
                         x-cloak>
                        <div class="portal-field__label-row">
                            <label for="business_name" class="portal-field__label">Business / Entity Name</label>
                            <span class="portal-field__badge">Required for juridical persons</span>
                        </div>
                        <input id="business_name"
                               name="business_name"
                               type="text"
                               value="{{ old('business_name') }}"
                               class="portal-input @error('business_name') portal-input--error @enderror"
                               placeholder="Enter business or entity name"
                               data-applicant-type-input
                               data-step-one-input
                               :disabled="applicantType !== juridicalType"
                               :required="applicantType === juridicalType">
                        <p class="portal-field__hint">Enter the registered corporation, partnership, or entity name.</p>
                        @error('business_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide"
                         data-applicant-type-section="{{ $juridicalApplicantType }}"
                         x-show="applicantType === juridicalType"
                         x-cloak>
                        <label for="business_address" class="portal-field__label">Business Address</label>
                        <textarea id="business_address"
                                  name="business_address"
                                  rows="4"
                                  class="portal-input portal-textarea @error('business_address') portal-input--error @enderror"
                                  placeholder="Enter complete business address"
                                  data-applicant-type-input
                                  data-step-one-input
                                  :disabled="applicantType !== juridicalType"
                                  :required="applicantType === juridicalType">{{ old('business_address') }}</textarea>
                        @error('business_address')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field portal-field--wide"
                         data-applicant-type-section="{{ $juridicalApplicantType }}"
                         x-show="applicantType === juridicalType"
                         x-cloak>
                        <label for="representative_name" class="portal-field__label">Representative Name</label>
                        <input id="representative_name"
                               name="representative_name"
                               type="text"
                               value="{{ old('representative_name') }}"
                               class="portal-input @error('representative_name') portal-input--error @enderror"
                               placeholder="Enter authorized representative name"
                               data-applicant-type-input
                               data-step-one-input
                               :disabled="applicantType !== juridicalType"
                               :required="applicantType === juridicalType">
                        @error('representative_name')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $juridicalApplicantType }}"
                         x-show="applicantType === juridicalType"
                         x-cloak>
                        <label for="representative_position" class="portal-field__label">Representative Position</label>
                        <input id="representative_position"
                               name="representative_position"
                               type="text"
                               value="{{ old('representative_position') }}"
                               class="portal-input @error('representative_position') portal-input--error @enderror"
                               placeholder="Enter position"
                               data-applicant-type-input
                               data-step-one-input
                               :disabled="applicantType !== juridicalType"
                               :required="applicantType === juridicalType">
                        @error('representative_position')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="portal-field"
                         data-applicant-type-section="{{ $juridicalApplicantType }}"
                         x-show="applicantType === juridicalType"
                         x-cloak>
                        <label for="representative_contact_number" class="portal-field__label">Representative Contact Number</label>
                        <input id="representative_contact_number"
                               name="representative_contact_number"
                               type="text"
                               value="{{ old('representative_contact_number', old('contact_number')) }}"
                               class="portal-input @error('representative_contact_number') portal-input--error @enderror"
                               placeholder="09xx xxx xxxx"
                               data-applicant-type-input
                               data-step-one-input
                               :disabled="applicantType !== juridicalType"
                               :required="applicantType === juridicalType">
                        @error('representative_contact_number')
                            <p class="portal-field__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="portal-form-actions mt-6"
                     style="position: sticky; bottom: 0; z-index: 10; margin: 1.5rem -0.5rem -1rem; padding: 1rem 0.5rem; background: linear-gradient(180deg, rgba(255,255,255,0.86), #ffffff 28%); border-top: 1px solid rgba(148, 163, 184, 0.22);">
                    <a href="{{ route('applications.index') }}" class="portal-button portal-button--ghost">
                        <span>Cancel</span>
                    </a>

                    <button type="button"
                            class="portal-button portal-button--primary portal-button--cta"
                            @click="nextStep($root)">
                        <span>Next</span>
                    </button>
                </div>
            </section>

            <section class="portal-section-card portal-section-card--form"
                     x-show="currentStep === 2"
                     x-cloak>
                <div class="portal-section-card__header">
                    <div>
                        <p class="portal-section-card__eyebrow">Document Checklist</p>
                        <h2 class="portal-section-card__title">Upload Requirements</h2>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <span class="portal-count-pill">Step 2 of 2</span>
                        <span class="portal-count-pill" data-checklist-count-pill>{{ $visibleRequirementCount }} requirements shown</span>
                    </div>
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
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="portal-form-actions mt-6"
                     style="position: sticky; bottom: 0; z-index: 10; margin: 1.5rem -0.5rem -1rem; padding: 1rem 0.5rem; background: linear-gradient(180deg, rgba(255,255,255,0.86), #ffffff 28%); border-top: 1px solid rgba(148, 163, 184, 0.22);">
                    <button type="button"
                            class="portal-button portal-button--ghost"
                            @click="goToStep(1)">
                        <span>Back</span>
                    </button>

                    <button type="submit" class="portal-button portal-button--primary portal-button--cta">
                        <span>Submit Application</span>
                    </button>
                </div>
            </section>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const applicationForm = document.querySelector('[data-application-autosave-form]');
    const applicantTypeSelect = document.getElementById('applicant_type');

    if (!applicantTypeSelect) {
        return;
    }

    const hasServerErrors = @json($errors->any());
    const sharedApplicantType = @json($sharedApplicantType);
    const requirementItems = document.querySelectorAll('[data-requirement-item]');
    const applicantTypeSections = document.querySelectorAll('[data-applicant-type-section]');
    const civilStatusInput = document.querySelector('[data-civil-status-input]');
    const checklistCountPill = document.querySelector('[data-checklist-count-pill]');
    const autosaveStatus = document.querySelector('[data-autosave-status]');
    const naturalType = @json($naturalApplicantType);

    const clearFieldValue = (field) => {
        let valueChanged = false;

        if (field.type === 'checkbox' || field.type === 'radio') {
            valueChanged = field.checked;
            field.checked = false;
        } else if (field.type !== 'file' && field.value !== '') {
            valueChanged = true;
            field.value = '';
        }

        if (valueChanged) {
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const updateRequirementVisibility = (clearInactiveFields = false) => {
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

        const isNatural = activeApplicantType === naturalType;
        const isMarriedNatural = isNatural && civilStatusInput?.value === 'Married';

        applicantTypeSections.forEach((section) => {
            const isActiveSection = section.dataset.applicantTypeSection === activeApplicantType;

            section.querySelectorAll('[data-applicant-type-input]').forEach((input) => {
                const isMarriedInput = input.hasAttribute('data-married-input');
                const isActiveInput = isActiveSection && (!isMarriedInput || isMarriedNatural);

                if (!isActiveInput && clearInactiveFields) {
                    clearFieldValue(input);
                }
            });
        });
    };

    const initApplicationAutosave = () => {
        if (!applicationForm || !applicationForm.dataset.autosaveKey) {
            return;
        }

        const storageKey = applicationForm.dataset.autosaveKey;
        let saveTimer = null;

        const setAutosaveStatus = (message) => {
            if (autosaveStatus) {
                autosaveStatus.textContent = message;
            }
        };

        const storageIsAvailable = () => {
            try {
                const testKey = `${storageKey}:test`;
                window.localStorage.setItem(testKey, '1');
                window.localStorage.removeItem(testKey);
                return true;
            } catch (error) {
                return false;
            }
        };

        if (!storageIsAvailable()) {
            setAutosaveStatus('Autosave unavailable');
            return;
        }

        const autosaveFields = () => Array.from(applicationForm.elements).filter((field) => {
            return field.name
                && field.name !== '_token'
                && field.type !== 'file'
                && field.type !== 'hidden'
                && ['INPUT', 'SELECT', 'TEXTAREA'].includes(field.tagName);
        });

        const restoreDraft = () => {
            if (hasServerErrors) {
                return;
            }

            try {
                const savedDraft = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
                const savedFields = savedDraft.fields || {};

                autosaveFields().forEach((field) => {
                    if (!Object.prototype.hasOwnProperty.call(savedFields, field.name)) {
                        return;
                    }

                    if (field.type === 'checkbox') {
                        field.checked = Boolean(savedFields[field.name]);
                        return;
                    }

                    if (field.type === 'radio') {
                        field.checked = savedFields[field.name] === field.value;
                        return;
                    }

                    const savedValue = savedFields[field.name] ?? '';

                    if (field.hasAttribute('data-profile-default') && savedValue === '' && field.defaultValue !== '') {
                        return;
                    }

                    field.value = savedValue;
                });

                if (Object.keys(savedFields).length > 0) {
                    setAutosaveStatus('Draft restored');
                    applicantTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    civilStatusInput?.dispatchEvent(new Event('change', { bubbles: true }));
                }
            } catch (error) {
                window.localStorage.removeItem(storageKey);
            }
        };

        const saveDraft = () => {
            const fields = {};

            autosaveFields().forEach((field) => {
                if (field.type === 'checkbox') {
                    fields[field.name] = field.checked;
                    return;
                }

                if (field.type === 'radio') {
                    if (field.checked) {
                        fields[field.name] = field.value;
                    }
                    return;
                }

                fields[field.name] = field.value;
            });

            window.localStorage.setItem(storageKey, JSON.stringify({
                updatedAt: new Date().toISOString(),
                fields,
            }));
            setAutosaveStatus('Draft saved');
        };

        const queueSave = () => {
            setAutosaveStatus('Saving draft...');
            window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(saveDraft, 300);
        };

        restoreDraft();

        applicationForm.addEventListener('input', queueSave);
        applicationForm.addEventListener('change', queueSave);
        applicationForm.addEventListener('submit', saveDraft);
    };

    applicantTypeSelect.addEventListener('change', () => updateRequirementVisibility(false));
    civilStatusInput?.addEventListener('change', () => updateRequirementVisibility(false));
    initApplicationAutosave();
    updateRequirementVisibility();
});
</script>
@endsection
