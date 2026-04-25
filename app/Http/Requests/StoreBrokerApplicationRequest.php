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

    public function rules(): array
    {
        return [
            'applicant_type' => ['required', 'string', Rule::in(array_keys(RequirementType::applicantTypeOptions()))],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'business_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->input('applicant_type') === RequirementType::APPLICANT_TYPE_JURIDICAL),
            ],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['required', 'string', 'max:50'],
            'requirements' => ['required', 'array'],
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

            if (
                !$opening->start_date ||
                !$opening->end_date ||
                now()->toDateString() < $opening->start_date->toDateString() ||
                now()->toDateString() > $opening->end_date->toDateString()
            ) {
                $validator->errors()->add('opening', 'This application opening is outside the allowed date range.');
            }

            $alreadyApplied = $this->user()
                ?->brokerApplications()
                ->where('application_opening_id', $opening->id)
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add('opening', 'You have already submitted an application for this stall opening.');
            }

            $requirements = $this->input('requirements', []);
            $requiredRequirementNames = collect(RequirementType::officialChecklistDefinitionsFor($applicantType))
                ->filter(fn (array $definition) => $definition['is_required'])
                ->pluck('requirement_name')
                ->all();

            RequirementType::ensureOfficialChecklistTypesExist();

            $requiredTypes = RequirementType::whereIn('requirement_name', $requiredRequirementNames)
                ->pluck('id', 'requirement_name');

            foreach ($requiredRequirementNames as $requirementName) {
                $requirementTypeId = $requiredTypes[$requirementName] ?? null;

                if (!$requirementTypeId) {
                    $validator->errors()->add(
                        'requirements',
                        'The application checklist is not configured yet. Please contact the LEEO office.'
                    );

                    return;
                }

                $file = data_get($requirements, $requirementTypeId . '.file');

                if (!$this->hasFile('requirements.' . $requirementTypeId . '.file') && empty($file)) {
                    $validator->errors()->add(
                        'requirements.' . $requirementTypeId . '.file',
                        'Please upload all required documents before submitting your application.'
                    );
                }
            }
        });
    }
}
