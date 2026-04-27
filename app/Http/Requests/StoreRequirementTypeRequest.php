<?php

namespace App\Http\Requests;

use App\Models\RequirementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequirementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_name' => ['required', 'string', 'max:255', 'unique:requirement_types,requirement_name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'audience' => [
                'required',
                Rule::in([
                    RequirementType::APPLICANT_TYPE_NATURAL,
                    RequirementType::APPLICANT_TYPE_JURIDICAL,
                    RequirementType::APPLICANT_TYPE_BOTH,
                ]),
            ],
            'is_required' => ['nullable', 'boolean'],
        ];
    }
}
