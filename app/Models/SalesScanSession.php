<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesScanSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'broker_id',
        'token',
        'expires_at',
        'closed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function createForBroker(int $brokerId): self
    {
        $activeSession = self::query()
            ->where('broker_id', $brokerId)
            ->whereNull('closed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($activeSession) {
            self::query()
                ->where('broker_id', $brokerId)
                ->whereNull('closed_at')
                ->where('id', '!=', $activeSession->id)
                ->update(['closed_at' => now()]);

            return $activeSession;
        }

        return self::create([
            'broker_id' => $brokerId,
            'token' => Str::random(48),
            'expires_at' => now()->addHours(2),
        ]);
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesScanSessionItem::class, 'sales_scan_session_id');
    }

    public function isActive(): bool
    {
        return $this->closed_at === null && $this->expires_at->isFuture();
    }
}
