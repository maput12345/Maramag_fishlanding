<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationReviewDraft extends Model
{
    use HasFactory;

    protected $table = 'ApplicationReviewDraft';

    protected $fillable = [
        'broker_application_id',
        'employee_id',
        'draft_payload',
        'last_saved_at',
    ];

    protected $casts = [
        'draft_payload' => 'array',
        'last_saved_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(BrokerApplication::class, 'broker_application_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
