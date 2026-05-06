<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrokerApplicationRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $this->user()
            && $application
            && (int) $application->user_id === (int) $this->user()->id
            && $application->application_status === 'Needs Revision';
    }

    public function rules(): array
    {
        return [
            'business_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['required', 'string', 'max:50'],
            'requirements' => ['required', 'array'],
            'requirements.*.id' => ['required', 'integer', 'exists:SubmittedRequirement,id'],
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
            $application = $this->route('application');

            if (!$application) {
                return;
            }

            if ($application->application_status !== 'Needs Revision') {
                $validator->errors()->add('application', 'Only applications marked Needs Revision can be resubmitted.');
            }

            $requirements = $application->requirements()
                ->select(['id', 'verification_status'])
                ->get()
                ->keyBy('id');
            $allowedRequirementIds = $requirements->keys()->all();
            $hasReplacementFile = false;

            foreach ($this->input('requirements', []) as $payload) {
                $requirementId = (int) ($payload['id'] ?? 0);

                if (!in_array($requirementId, $allowedRequirementIds, true)) {
                    $validator->errors()->add('requirements', 'One or more requirement rows do not belong to this application.');
                    break;
                }

                $requirement = $requirements->get($requirementId);

                if (!$requirement || strcasecmp((string) $requirement->verification_status, 'Verified') === 0) {
                    continue;
                }

                if ($this->hasFile('requirements.' . $requirementId . '.file')) {
                    $hasReplacementFile = true;
                }
            }

            $hasRequirementForRevision = $requirements
                ->contains(fn ($requirement) => strcasecmp((string) $requirement->verification_status, 'Verified') !== 0);

            if (!$hasRequirementForRevision) {
                $validator->errors()->add('requirements', 'There are no requirements currently open for revision.');
            }

            if ($hasRequirementForRevision && !$hasReplacementFile) {
                $validator->errors()->add('requirements', 'Please upload at least one replacement file before resubmitting your revision.');
            }
        });
    }
}
