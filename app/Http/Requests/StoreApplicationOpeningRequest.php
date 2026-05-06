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
            'stall_ids' => ['required', 'array', 'min:1'],
            'stall_ids.*' => ['integer', 'distinct', 'exists:Stall,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'bidding_date' => ['required', 'date', 'after_or_equal:start_date'],
            'bidding_time' => ['required', 'date_format:H:i'],
            'bidding_location' => ['required', 'string', 'max:255'],
            'requirement_type_ids' => ['required', 'array', 'min:1'],
            'requirement_type_ids.*' => ['integer', 'exists:RequirementType,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $stallIds = collect($this->input('stall_ids', []))
                ->map(fn ($stallId) => (int) $stallId)
                ->filter()
                ->unique()
                ->values();

            if ($stallIds->isNotEmpty()) {
                $nonVacantStallNames = Stall::query()
                    ->whereIn('id', $stallIds)
                    ->where('stall_status', '!=', 'Vacant')
                    ->get()
                    ->map(fn (Stall $stall) => $stall->display_name)
                    ->values();

                if ($nonVacantStallNames->isNotEmpty()) {
                    $validator->errors()->add(
                        'stall_ids',
                        'Only vacant stalls can be opened. Please remove: ' . $nonVacantStallNames->implode(', ') . '.'
                    );
                }

                $stallsWithActiveOpenings = ApplicationOpening::query()
                    ->whereIn('stall_id', $stallIds)
                    ->whereIn('opening_status', ['Open', 'Closed'])
                    ->with('stall:id,stall_number')
                    ->get()
                    ->map(fn (ApplicationOpening $opening) => $opening->stall?->display_name)
                    ->filter()
                    ->unique()
                    ->values();

                if ($stallsWithActiveOpenings->isNotEmpty()) {
                    $validator->errors()->add(
                        'stall_ids',
                        'These stalls already have an active application opening: ' . $stallsWithActiveOpenings->implode(', ') . '.'
                    );
                }
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
