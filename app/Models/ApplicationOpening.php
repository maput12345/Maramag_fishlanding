<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ApplicationOpening extends Model
{
    use HasFactory;

    protected $table = 'ApplicationOpening';

    public const AVAILABLE_STALL_STATUSES = [
        'Vacant',
        'Open for Application',
    ];

    protected $fillable = [
        'stall_id',
        'opened_by_employee_id',
        'start_date',
        'end_date',
        'bidding_date',
        'bidding_location',
        'opening_status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'bidding_date' => 'date',
    ];

    /**
     * Get the stall receiving applications.
     */
    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'stall_id');
    }

    /**
     * Get the employee who opened the application window.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'opened_by_employee_id');
    }

    /**
     * Get the applications submitted for this opening.
     */
    public function brokerApplications(): HasMany
    {
        return $this->hasMany(BrokerApplication::class, 'application_opening_id');
    }

    /**
     * Get the requirement snapshot saved for this opening.
     */
    public function openingRequirements(): HasMany
    {
        return $this->hasMany(OpeningRequirement::class);
    }

    /**
     * Get the requirement types selected for this opening.
     */
    public function requirementTypes(): BelongsToMany
    {
        return $this->belongsToMany(RequirementType::class, 'OpeningRequirement')
            ->withPivot(['is_required', 'audience', 'sort_order'])
            ->withTimestamps()
            ->orderBy('OpeningRequirement.sort_order')
            ->orderBy('RequirementType.requirement_name');
    }

    /**
     * Scope currently open application windows.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('opening_status', 'Open')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString());
    }

    /**
     * Scope openings where applicants can still create an account and apply.
     */
    public function scopeAvailableForApplication(Builder $query): Builder
    {
        return $query
            ->open()
            ->whereHas('stall', function (Builder $stallQuery) {
                $stallQuery->whereIn('stall_status', self::AVAILABLE_STALL_STATUSES);
            });
    }

    /**
     * Check whether the linked stall is still available for applicants.
     */
    public function hasAvailableStall(): bool
    {
        $this->loadMissing('stall');

        return $this->stall
            && in_array($this->stall->stall_status, self::AVAILABLE_STALL_STATUSES, true);
    }

    /**
     * Get this opening's saved checklist, falling back to the official list for old openings.
     */
    public function resolvedRequirementTypes(): Collection
    {
        $selectedRequirementTypes = $this->relationLoaded('requirementTypes')
            ? $this->requirementTypes
            : $this->requirementTypes()->get();

        if ($selectedRequirementTypes->isNotEmpty()) {
            return $selectedRequirementTypes;
        }

        return RequirementType::officialChecklistTypes();
    }

    /**
     * Build the applicant-form definition map from the saved opening snapshot.
     */
    public function requirementDefinitionMap(): array
    {
        $officialDefinitions = RequirementType::officialChecklistMapByName();

        return $this->resolvedRequirementTypes()
            ->mapWithKeys(function (RequirementType $requirementType) use ($officialDefinitions) {
                $officialDefinition = $officialDefinitions[$requirementType->requirement_name] ?? [];

                return [
                    $requirementType->requirement_name => [
                        'audience' => $requirementType->pivot?->audience
                            ?? $requirementType->audience
                            ?? ($officialDefinition['audience'] ?? RequirementType::APPLICANT_TYPE_BOTH),
                        'is_required' => (bool) (
                            $requirementType->pivot?->is_required
                            ?? $requirementType->is_required
                            ?? ($officialDefinition['is_required'] ?? true)
                        ),
                        'description' => $requirementType->description
                            ?? ($officialDefinition['description'] ?? 'Upload the official supporting document for this checklist item.'),
                        'sort_order' => (int) (
                            $requirementType->pivot?->sort_order
                            ?? $requirementType->sort_order
                            ?? ($officialDefinition['sort_order'] ?? 999)
                        ),
                    ],
                ];
            })
            ->all();
    }

    /**
     * Get required requirement types for the selected applicant category.
     */
    public function requiredRequirementTypesFor(string $applicantType): Collection
    {
        $definitionMap = $this->requirementDefinitionMap();

        return $this->resolvedRequirementTypes()
            ->filter(function (RequirementType $requirementType) use ($definitionMap, $applicantType) {
                $definition = $definitionMap[$requirementType->requirement_name] ?? [];

                return (bool) ($definition['is_required'] ?? false)
                    && in_array(
                        $definition['audience'] ?? RequirementType::APPLICANT_TYPE_BOTH,
                        [$applicantType, RequirementType::APPLICANT_TYPE_BOTH],
                        true
                    );
            })
            ->values();
    }
}
