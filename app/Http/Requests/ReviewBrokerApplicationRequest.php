<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewBrokerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_status' => ['required', Rule::in(['Under Review', 'Needs Revision', 'Rejected', 'Qualified'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['required', 'array', 'min:1'],
            'requirements.*.id' => ['required', 'exists:application_requirements,id'],
            'requirements.*.verification_status' => ['required', Rule::in(['Pending', 'Verified', 'Rejected'])],
            'requirements.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $application = $this->route('application');

            if (!$application) {
                return;
            }

            $allowedIds = $application->requirements()->pluck('id')->all();

            foreach ($this->input('requirements', []) as $payload) {
                if (!in_array((int) ($payload['id'] ?? 0), $allowedIds, true)) {
                    $validator->errors()->add('requirements', 'One or more requirement review rows do not belong to this application.');
                    break;
                }
            }
        });
    }
}
