<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BrokerFishTypeAssignment extends Model
{
    use HasFactory;

    protected $table = 'BrokerFishTypeAssignment';

    protected $fillable = [
        'broker_id',
        'fish_type_id',
        'display_name',
        'display_description',
    ];

    /**
     * Get the broker for this assignment.
     */
    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    /**
     * Get the fish type for this assignment.
     */
    public function fishType(): BelongsTo
    {
        return $this->belongsTo(FishType::class, 'fish_type_id');
    }

    /**
     * Get the price history for this broker fish type assignment.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(FishPriceRecord::class, 'broker_fish_type_id');
    }

    /**
     * Get the most recent price for this broker fish type pair.
     */
    public function latestPrice(): HasOne
    {
        return $this->hasOne(FishPriceRecord::class, 'broker_fish_type_id')->latestOfMany();
    }

    public function getDisplayNameAttribute(?string $value): ?string
    {
        return $value ?: $this->fishType?->name;
    }

    public function getDisplayDescriptionAttribute(?string $value): ?string
    {
        return $value ?: $this->fishType?->description;
    }

    public static function resolveDisplayName(?int $brokerId, ?FishType $fishType): ?string
    {
        if (!$fishType) {
            return null;
        }

        if (!$brokerId) {
            return $fishType->name;
        }

        $displayName = self::query()
            ->where('broker_id', $brokerId)
            ->where('fish_type_id', $fishType->id)
            ->value('display_name');

        return $displayName ?: $fishType->name;
    }

    public static function resolveDisplayDescription(?int $brokerId, ?FishType $fishType): ?string
    {
        if (!$fishType) {
            return null;
        }

        if (!$brokerId) {
            return $fishType->description;
        }

        $displayDescription = self::query()
            ->where('broker_id', $brokerId)
            ->where('fish_type_id', $fishType->id)
            ->value('display_description');

        return $displayDescription ?: $fishType->description;
    }
}
