<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationOpening extends Model
{
    use HasFactory;

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
     * Scope currently open application windows.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('opening_status', 'Open')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString());
    }
}
