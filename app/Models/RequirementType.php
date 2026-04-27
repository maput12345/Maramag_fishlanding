<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class RequirementType extends Model
{
    use HasFactory;

    public const APPLICANT_TYPE_NATURAL = 'natural_person';
    public const APPLICANT_TYPE_JURIDICAL = 'juridical_person';
    public const APPLICANT_TYPE_BOTH = 'both';

    protected $fillable = [
        'requirement_name',
        'is_required',
        'description',
        'audience',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get application requirement entries for this type.
     */
    public function applicationRequirements(): HasMany
    {
        return $this->hasMany(ApplicationRequirement::class, 'requirement_type_id');
    }

    /**
     * Get vacancy checklist snapshots using this requirement.
     */
    public function openingRequirements(): HasMany
    {
        return $this->hasMany(ApplicationOpeningRequirement::class, 'requirement_type_id');
    }

    /**
     * Applicant type options used by the broker application form.
     *
     * @return array<string, string>
     */
    public static function applicantTypeOptions(): array
    {
        return [
            self::APPLICANT_TYPE_NATURAL => 'Natural Person',
            self::APPLICANT_TYPE_JURIDICAL => 'Juridical Person',
        ];
    }

    /**
     * Return the official checklist used for broker stall bidding applications.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function officialChecklistDefinitions(): array
    {
        return [
            [
                'requirement_name' => 'Certified True Copy of Birth Certificate',
                'description' => 'Natural Person: 1 original and 1 photocopy from the City Civil Registrar\'s Office or Philippine Statistics Authority (PSA).',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_NATURAL,
                'sort_order' => 10,
            ],
            [
                'requirement_name' => 'Barangay Clearance and Community Tax Certificate',
                'description' => 'All Applicants: 1 original and 1 photocopy from the Barangay where the applicant resides or the entity is registered.',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_BOTH,
                'sort_order' => 20,
            ],
            [
                'requirement_name' => 'Municipal Trial Court and Regional Trial Court Clearance',
                'description' => 'Natural Person: 1 original and 1 photocopy issued by the appropriate court with jurisdiction over the residence of the applicant, from the Municipal Trial Court (MTC) or Regional Trial Court (RTC).',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_NATURAL,
                'sort_order' => 30,
            ],
            [
                'requirement_name' => 'NBI Clearance',
                'description' => 'Natural Person: 1 original and 1 photocopy from the National Bureau of Investigation (NBI).',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_NATURAL,
                'sort_order' => 40,
            ],
            [
                'requirement_name' => 'Tax Clearance',
                'description' => 'All Applicants: 1 original and 1 photocopy from the Municipal Treasurer\'s Office for payment of all taxes prescribed under the Local Government Code.',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_BOTH,
                'sort_order' => 50,
            ],
            [
                'requirement_name' => 'Medical / Health Certificate',
                'description' => 'Natural Person: 1 original and 1 photocopy from the Municipal Health Office.',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_NATURAL,
                'sort_order' => 60,
            ],
            [
                'requirement_name' => 'Community Fax Certificate',
                'description' => 'Natural Person: 1 original and 1 photocopy from the Barangay Office.',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_NATURAL,
                'sort_order' => 70,
            ],
            [
                'requirement_name' => 'Letter of Intent',
                'description' => 'All Applicants: submit a signed letter of intent together with the application.',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_BOTH,
                'sort_order' => 80,
            ],
            [
                'requirement_name' => 'Other Documents as May Be Required by the Municipal Market Committee',
                'description' => 'Natural Person: upload any additional documents as may be required by the Municipal Market Committee.',
                'is_required' => false,
                'audience' => self::APPLICANT_TYPE_NATURAL,
                'sort_order' => 90,
            ],
            [
                'requirement_name' => 'Certificate of Incorporation (Corporation) or Partnership',
                'description' => 'Juridical Person: 1 original and 1 photocopy from the Securities and Exchange Commission (SEC).',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_JURIDICAL,
                'sort_order' => 100,
            ],
            [
                'requirement_name' => 'DTI Registration',
                'description' => 'Juridical Person: 1 original and 1 photocopy from the Department of Trade and Industry (DTI).',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_JURIDICAL,
                'sort_order' => 110,
            ],
            [
                'requirement_name' => 'BIR Certificate of Registration',
                'description' => 'Juridical Person: 1 original and 1 photocopy from the Bureau of Internal Revenue (BIR).',
                'is_required' => true,
                'audience' => self::APPLICANT_TYPE_JURIDICAL,
                'sort_order' => 120,
            ],
        ];
    }

    /**
     * Return official checklist definitions filtered by applicant type.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function officialChecklistDefinitionsFor(?string $applicantType = null): array
    {
        return array_values(array_filter(
            self::officialChecklistDefinitions(),
            function (array $definition) use ($applicantType) {
                if ($applicantType === null) {
                    return true;
                }

                return in_array($definition['audience'], [$applicantType, self::APPLICANT_TYPE_BOTH], true);
            }
        ));
    }

    /**
     * Return official checklist definitions indexed by requirement name.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function officialChecklistMapByName(): array
    {
        $definitions = [];

        foreach (self::officialChecklistDefinitions() as $definition) {
            $definitions[$definition['requirement_name']] = $definition;
        }

        return $definitions;
    }

    /**
     * Return the requirement names that belong to the official checklist.
     *
     * @return array<int, string>
     */
    public static function officialChecklistNames(): array
    {
        return array_column(self::officialChecklistDefinitions(), 'requirement_name');
    }

    /**
     * Return legacy requirement names that should be normalized in place.
     *
     * @return array<string, string>
     */
    public static function legacyRequirementNameMap(): array
    {
        return [
            'Other Documents Required by the Municipal Market Committee'
                => 'Other Documents as May Be Required by the Municipal Market Committee',
            'Certificate of Incorporation or Partnership'
                => 'Certificate of Incorporation (Corporation) or Partnership',
        ];
    }

    /**
     * Ensure the official checklist rows exist in the database.
     */
    public static function ensureOfficialChecklistTypesExist(): void
    {
        foreach (static::legacyRequirementNameMap() as $legacyName => $canonicalName) {
            $legacyRequirementType = static::query()
                ->where('requirement_name', $legacyName)
                ->first();

            if (!$legacyRequirementType) {
                continue;
            }

            $canonicalRequirementType = static::query()
                ->where('requirement_name', $canonicalName)
                ->first();

            if ($canonicalRequirementType) {
                continue;
            }

            $legacyRequirementType->update([
                'requirement_name' => $canonicalName,
            ]);
        }

        foreach (self::officialChecklistDefinitions() as $definition) {
            static::updateOrCreate(
                ['requirement_name' => $definition['requirement_name']],
                [
                    'is_required' => $definition['is_required'],
                    'description' => $definition['description'],
                    'audience' => $definition['audience'],
                    'sort_order' => $definition['sort_order'],
                ]
            );
        }
    }

    /**
     * Get the official checklist rows ordered by their configured sort order.
     */
    public static function officialChecklistTypes(): Collection
    {
        static::ensureOfficialChecklistTypesExist();

        $definitions = static::officialChecklistMapByName();

        return static::query()
            ->whereIn('requirement_name', static::officialChecklistNames())
            ->get()
            ->sortBy(fn (self $requirementType) => $definitions[$requirementType->requirement_name]['sort_order'] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Get all requirements LEEO can choose when declaring a vacancy.
     */
    public static function selectableChecklistTypes(): Collection
    {
        static::ensureOfficialChecklistTypesExist();

        return static::query()
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('requirement_name')
            ->get();
    }

    /**
     * Get the UI label for an applicant type audience value.
     */
    public static function applicantTypeLabel(string $applicantType): string
    {
        return match ($applicantType) {
            self::APPLICANT_TYPE_NATURAL => 'Natural Person',
            self::APPLICANT_TYPE_JURIDICAL => 'Juridical Person',
            default => 'All Applicants',
        };
    }
}
