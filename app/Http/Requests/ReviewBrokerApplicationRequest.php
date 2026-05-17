<?php

namespace App\Http\Requests;

use App\Constants\ApplicationStatusConstant;
use App\Constants\RequirementVerificationStatusConstant;
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
            'application_status' => ['required', Rule::in(ApplicationStatusConstant::reviewStatuses())],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['required', 'array', 'min:1'],
            'requirements.*.id' => ['required', 'exists:SubmittedRequirement,id'],
            'requirements.*.verification_status' => ['required', Rule::in(RequirementVerificationStatusConstant::all())],
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

            $requirementStatuses = collect($this->input('requirements', []))
                ->filter(fn ($payload) => is_array($payload))
                ->pluck('verification_status');
            $allRequirementsVerified = $requirementStatuses->isNotEmpty()
                && $requirementStatuses->every(fn ($status) => $status === RequirementVerificationStatusConstant::VERIFIED);
            $willBeQualified = $this->input('application_status') !== ApplicationStatusConstant::REJECTED
                && ($this->input('application_status') === ApplicationStatusConstant::QUALIFIED || $allRequirementsVerified);

            if (
                $this->input('application_status') === ApplicationStatusConstant::QUALIFIED
                && !$application->canBeQualified($this->input('requirements', []))
            ) {
                $validator->errors()->add(
                    'application_status',
                    'All application requirements must be marked Verified before the application can be qualified.'
                );
            }

            if ($this->input('application_status') === ApplicationStatusConstant::REJECTED && blank($this->input('remarks'))) {
                $validator->errors()->add(
                    'remarks',
                    'Enter LEEO remarks explaining why this application was rejected.'
                );
            }

            if (!$willBeQualified) {
                return;
            }

            $opening = $application->relationLoaded('applicationOpening')
                ? $application->applicationOpening
                : $application->applicationOpening()
                    ->with('openingBatch:id,bidding_date,bidding_time,bidding_location')
                    ->first(['id', 'opening_batch_id']);

            if (!$opening?->bidding_date || !$opening?->bidding_time || blank($opening->bidding_location)) {
                $validator->errors()->add(
                    'application_status',
                    'Set the bidding date, bidding time, and bidding location on the application opening before qualifying this applicant.'
                );
            }
        });
    }
}
