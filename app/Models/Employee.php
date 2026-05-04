<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'Employee';

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'position',
    ];

    protected $appends = ['name'];

    /**
     * Get the user account for this employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get application openings created by this employee.
     */
    public function openedApplicationOpenings(): HasMany
    {
        return $this->hasMany(ApplicationOpening::class, 'opened_by_employee_id');
    }

    /**
     * Get broker applications reviewed by this employee.
     */
    public function reviewedBrokerApplications(): HasMany
    {
        return $this->hasMany(BrokerApplication::class, 'reviewed_by_employee_id');
    }

    /**
     * Get broker applications selected by this employee.
     */
    public function selectedBrokerApplications(): HasMany
    {
        return $this->hasMany(BrokerApplication::class, 'selected_by_employee_id');
    }

    /**
     * Get application requirements verified by this employee.
     */
    public function verifiedApplicationRequirements(): HasMany
    {
        return $this->hasMany(SubmittedRequirement::class, 'verified_by_employee_id');
    }

    /**
     * Get the full display name for this employee profile.
     */
    public function getNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter()->implode(' ');
    }

    /**
     * Create the employee profile for a user.
     */
    public static function createProfile(int $userId, array $data = []): self
    {
        $nameParts = User::extractNameParts($data);

        return static::create([
            'user_id' => $userId,
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'position' => $data['position'] ?? null,
        ]);
    }

    /**
     * Update employee profile data.
     */
    public function updateProfile(array $data): bool
    {
        $nameParts = User::extractNameParts($data, [
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
        ]);

        return $this->update([
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'suffix' => $data['suffix'] ?? $this->suffix,
            'contact_number' => $data['contact_number'] ?? $this->contact_number,
            'position' => $data['position'] ?? $this->position,
        ]);
    }
}
