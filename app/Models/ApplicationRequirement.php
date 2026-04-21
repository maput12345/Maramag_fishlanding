<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'requirement_type_id',
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
    ];

    protected $appends = ['file_url'];

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
}
