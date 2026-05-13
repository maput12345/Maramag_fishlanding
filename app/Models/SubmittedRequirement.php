<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmittedRequirement extends Model
{
    use HasFactory;

    protected $table = 'SubmittedRequirement';

    protected $fillable = [
        'application_id',
        'requirement_type_id',
        'custom_title',
        'custom_description',
        'is_additional',
        'verified_by_employee_id',
        'file_path',
        'document_number',
        'issuing_office',
        'issue_date',
        'expiry_date',
        'verification_status',
        'verification_date',
        'remarks',
        'uploaded_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verification_date' => 'datetime',
        'uploaded_at' => 'datetime',
        'is_additional' => 'boolean',
    ];

    protected $appends = ['file_url', 'display_name'];

    /**
     * Get the parent application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(BrokerApplication::class, 'application_id');
    }

    /**
     * Get the requirement type definition.
     */
    public function requirementType(): BelongsTo
    {
        return $this->belongsTo(RequirementType::class, 'requirement_type_id');
    }

    /**
     * Get the employee who verified this requirement.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'verified_by_employee_id');
    }

    /**
     * Expose a convenient public URL for uploaded files.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Display custom applicant-specific requirements without a global type.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->custom_title
            ?: ($this->requirementType?->requirement_name ?? 'Additional Requirement');
    }
}
