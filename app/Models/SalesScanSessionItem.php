<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesScanSessionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_scan_session_id',
        'fish_box_id',
        'qr_code',
        'status',
        'message',
        'payload',
        'consumed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'consumed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SalesScanSession::class, 'sales_scan_session_id');
    }

    public function fishBox(): BelongsTo
    {
        return $this->belongsTo(FishBox::class, 'fish_box_id');
    }
}
