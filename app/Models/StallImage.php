<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_id',
        'image_path',
        'sort_order',
    ];

    /**
     * Get the parent stall for this gallery image.
     */
    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'stall_id');
    }
}
