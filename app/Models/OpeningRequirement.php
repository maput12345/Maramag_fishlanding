<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningRequirement extends Model
{
    use HasFactory;

    protected $table = 'OpeningRequirement';

    protected $fillable = [
        'application_opening_id',
        'requirement_type_id',
        'is_required',
        'audience',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function applicationOpening(): BelongsTo
    {
        return $this->belongsTo(ApplicationOpening::class);
    }

    public function requirementType(): BelongsTo
    {
        return $this->belongsTo(RequirementType::class);
    }
}
