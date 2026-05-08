<?php

namespace App\Http\Requests;

use App\Models\ApplicationOpening;
use App\Models\RequirementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrokerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('applicant_type') !== RequirementType::APPLICANT_TYPE_JURIDICAL) {
            return;
        }

        $representativeContactNumber = $this->input('representative_contact_number');

        if (!$representativeContactNumber && $this->filled('contact_number')) {
            $this->merge([
                'representative_contact_number' => $this->input('contact_number'),
            ]);
        }
    }

    public function rules(): array
    {
        $naturalPerson = RequirementType::APPLICANT_TYPE_NATURAL;
        $juridicalPerson = RequirementType::APPLICANT_TYPE_JURIDICAL;

        return [
            'applicant_type' => ['required', 'string', Rule::in(array_keys(RequirementType::applicantTypeOptions()))],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'civil_status' => ['exclude_unless:applicant_type,' . $naturalPerson, 'required', 'string', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'spouse_name' => ['exclude_unless:applicant_type,' . $naturalPerson, 'required_if:civil_status,Married', 'nullable', 'string', 'max:255'],
            'spouse_contact_number' => ['exclude_unless:applicant_type,' . $naturalPerson, 'nullable', 'string', 'max:50'],
            'business_name' => [
                'exclude_unless:applicant_type,' . $juridicalPerson,
                'required',
                'string',
                'max:255',
            ],
            'business_address' => ['exclude_unless:applicant_type,' . $juridicalPerson, 'required', 'string', 'max:1000'],
            'representative_name' => ['exclude_unless:applicant_type,' . $juridicalPerson, 'required', 'string', 'max:255'],
            'representative_position' => ['exclude_unless:applicant_type,' . $juridicalPerson, 'required', 'string', 'max:255'],
            'representative_contact_number' => ['exclude_unless:applicant_type,' . $juridicalPerson, 'required', 'string', 'max:50'],
            'address' => ['exclude_unless:applicant_type,' . $naturalPerson, 'required', 'string', 'max:1000'],
            'contact_number' => ['exclude_unless:applicant_type,' . $naturalPerson, 'required', 'string', 'max:50'],
            'requirements' => ['nullable', 'array'],
            'requirements.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'requirements.*.document_number' => ['nullable', 'string', 'max:255'],
            'requirements.*.issuing_office' => ['nullable', 'string', 'max:255'],
            'requirements.*.issue_date' => ['nullable', 'date'],
            'requirements.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var ApplicationOpening|null $opening */
            $opening = $this->route('opening');

            if (!$opening) {
                return;
            }

            $applicantType = $this->input('applicant_type');

            if (!in_array($applicantType, array_keys(RequirementType::applicantTypeOptions()), true)) {
                return;
            }

            if ($opening->opening_status !== 'Open') {
                $validator->errors()->add('opening', 'This application opening is no longer accepting submissions.');
            }

            if (!$opening->hasAvailableStall()) {
                $validator->errors()->add('opening', 'This stall is no longer available for applications.');
            }

            if (
                !$opening->start_date ||
                !$opening->end_date ||
                now()->toDateString() < $opening->start_date->toDateString() ||
                now()->toDateString() > $opening->end_date->toDateString()
            ) {
                $validator->errors()->add('opening', 'This application opening is outside the allowed date range.');
            }

            $sameOpeningAlreadySubmitted = $this->user()
                ?->brokerApplications()
                ->where('application_opening_id', $opening->id)
                ->exists();

            if ($sameOpeningAlreadySubmitted) {
                $validator->errors()->add('opening', 'You already submitted an application for this stall opening.');
            }

            $alreadyApplied = $this->user()
                ?->brokerApplications()
                ->whereNotIn('application_status', ['Rejected', 'Not Selected', 'Cancelled'])
                ->exists();

            if ($alreadyApplied && !$sameOpeningAlreadySubmitted) {
                $validator->errors()->add('opening', 'You already have an active application for the current open stalls.');
            }

            foreach ($opening->requiredRequirementTypesFor($applicantType) as $requirementType) {
                if (!$this->hasFile('requirements.' . $requirementType->id . '.file')) {
                    $validator->errors()->add(
                        'requirements.' . $requirementType->id . '.file',
                        'Please upload all required documents before submitting your application.'
                    );
                }
            }
        });
    }
}
