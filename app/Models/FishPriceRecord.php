<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishPriceRecord extends Model
{
    use HasFactory;

    protected $table = 'FishPriceRecord';

    protected $fillable = [
        'broker_fish_type_id',
        'price',
        'default_cost_price',
        'price_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'default_cost_price' => 'decimal:2',
        'price_date' => 'date',
    ];

    /**
     * @return BelongsTo
     */
    public function brokerFishType(): BelongsTo
    {
        return $this->belongsTo(BrokerFishTypeAssignment::class, 'broker_fish_type_id');
    }

    /**
     * Convenience accessor for the linked fish type.
     */
    public function getFishTypeAttribute(): ?FishType
    {
        return $this->brokerFishType?->fishType;
    }

    /**
     * Convenience accessor for the linked broker.
     */
    public function getBrokerAttribute(): ?Broker
    {
        return $this->brokerFishType?->broker;
    }
}
