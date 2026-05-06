<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpeningBatch extends Model
{
    use HasFactory;

    protected $table = 'OpeningBatch';

    protected $fillable = [
        'opened_by_employee_id',
        'start_date',
        'end_date',
        'bidding_date',
        'bidding_time',
        'bidding_location',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'bidding_date' => 'date',
        'bidding_time' => 'datetime:H:i',
    ];

    protected $appends = ['display_label'];

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'opened_by_employee_id');
    }

    public function applicationOpenings(): HasMany
    {
        return $this->hasMany(ApplicationOpening::class, 'opening_batch_id');
    }

    public function brokerApplications(): HasMany
    {
        return $this->hasMany(BrokerApplication::class, 'opening_batch_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        $this->loadMissing('applicationOpenings.stall:id,stall_number');

        $stallNumbers = $this->applicationOpenings
            ->pluck('stall.stall_number')
            ->filter()
            ->sort(SORT_NATURAL)
            ->values()
            ->implode(', ');

        $dateLabel = optional($this->start_date)->format('M j')
            . ' to '
            . optional($this->end_date)->format('M j, Y');

        return 'Batch #' . $this->id
            . ' - ' . $dateLabel
            . ' - Stalls ' . ($stallNumbers ?: 'N/A');
    }
}
