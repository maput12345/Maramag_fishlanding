<?php

namespace App\Http\Requests;

use App\Models\ApplicationOpening;
use App\Models\RequirementType;
use App\Models\Stall;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stall_id' => ['required', 'exists:stalls,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'bidding_date' => ['required', 'date', 'after_or_equal:start_date'],
            'bidding_location' => ['required', 'string', 'max:255'],
            'requirement_type_ids' => ['required', 'array', 'min:1'],
            'requirement_type_ids.*' => ['integer', 'exists:requirement_types,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $stallId = (int) $this->input('stall_id');

            if (!$stallId) {
                return;
            }

            $stall = Stall::find($stallId);

            if ($stall && $stall->stall_status === 'Occupied') {
                $validator->errors()->add('stall_id', 'Occupied stalls cannot be opened for new applications.');
            }

            $hasActiveOpening = ApplicationOpening::where('stall_id', $stallId)
                ->whereIn('opening_status', ['Open', 'Closed'])
                ->exists();

            if ($hasActiveOpening) {
                $validator->errors()->add('stall_id', 'This stall already has an active application opening.');
            }

            $selectedRequirementIds = collect($this->input('requirement_type_ids', []))
                ->map(fn ($requirementId) => (int) $requirementId)
                ->filter()
                ->unique()
                ->values();

            if ($selectedRequirementIds->isEmpty()) {
                return;
            }

            $selectedRequirements = RequirementType::query()
                ->whereIn('id', $selectedRequirementIds)
                ->get();

            $hasNaturalRequirement = $selectedRequirements->contains(function (RequirementType $requirementType) {
                return in_array($requirementType->audience, [
                    RequirementType::APPLICANT_TYPE_NATURAL,
                    RequirementType::APPLICANT_TYPE_BOTH,
                ], true);
            });

            $hasJuridicalRequirement = $selectedRequirements->contains(function (RequirementType $requirementType) {
                return in_array($requirementType->audience, [
                    RequirementType::APPLICANT_TYPE_JURIDICAL,
                    RequirementType::APPLICANT_TYPE_BOTH,
                ], true);
            });

            if (!$hasNaturalRequirement || !$hasJuridicalRequirement) {
                $validator->errors()->add(
                    'requirement_type_ids',
                    'Select at least one requirement that applies to natural persons and one that applies to juridical persons.'
                );
            }
        });
    }
}
