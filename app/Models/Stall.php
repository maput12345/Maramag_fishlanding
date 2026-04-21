<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stall extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_number',
        'stall_status',
        'remarks',
    ];

    protected $appends = ['display_name'];

    /**
     * Get application openings for this stall.
     */
    public function applicationOpenings(): HasMany
    {
        return $this->hasMany(ApplicationOpening::class, 'stall_id');
    }

    /**
     * Get the active broker occupying this stall.
     */
    public function broker(): HasOne
    {
        return $this->hasOne(Broker::class, 'stall_id');
    }

    /**
     * Display a readable stall label across the UI.
     */
    public function getDisplayNameAttribute(): string
    {
        return 'Stall ' . $this->stall_number;
    }
}
