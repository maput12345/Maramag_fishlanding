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
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['required', 'string', 'max:50'],
            'requirements' => ['required', 'array'],
            'requirements.*.id' => ['required', 'integer', 'exists:application_requirements,id'],
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

            $allowedRequirementIds = $application->requirements()->pluck('id')->all();

            foreach ($this->input('requirements', []) as $payload) {
                if (!in_array((int) ($payload['id'] ?? 0), $allowedRequirementIds, true)) {
                    $validator->errors()->add('requirements', 'One or more requirement rows do not belong to this application.');
                    break;
                }
            }
        });
    }
}
