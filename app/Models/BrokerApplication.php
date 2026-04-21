<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BrokerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'application_opening_id',
        'reviewed_by_employee_id',
        'selected_by_employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'business_name',
        'address',
        'contact_number',
        'application_status',
        'submitted_at',
        'review_date',
        'selected_at',
        'remarks',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'review_date' => 'datetime',
        'selected_at' => 'datetime',
    ];

    protected $appends = ['name'];

    /**
     * Get the applicant account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the opening this application belongs to.
     */
    public function applicationOpening(): BelongsTo
    {
        return $this->belongsTo(ApplicationOpening::class, 'application_opening_id');
    }

    /**
     * Get the reviewing employee.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewed_by_employee_id');
    }

    /**
     * Get the employee who selected the winner.
     */
    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'selected_by_employee_id');
    }

    /**
     * Get uploaded requirements for this application.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ApplicationRequirement::class, 'application_id');
    }

    /**
     * Get the broker profile created from this application.
     */
    public function broker(): HasOne
    {
        return $this->hasOne(Broker::class, 'application_id');
    }

    /**
     * Get the full applicant name for the UI.
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
}
