<?php

namespace App\Models;

use App\Constants\FishBoxStatusConstant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishBoxStockCycle extends Model
{
    use HasFactory;

    protected $table = 'FishBoxStockCycle';

    protected $fillable = [
        'fish_box_id',
        'fish_type_id',
        'created_by_user_id',
        'purchase_date',
        'cost_price',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'cost_price' => 'decimal:2',
    ];

    /**
     * Get the physical fish box for this purchase cycle.
     */
    public function fishBox(): BelongsTo
    {
        return $this->belongsTo(FishBox::class, 'fish_box_id');
    }

    /**
     * Get the fish type stocked in this purchase cycle.
     */
    public function fishType(): BelongsTo
    {
        return $this->belongsTo(FishType::class, 'fish_type_id');
    }

    /**
     * Get the user who recorded this purchase cycle.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get inventory logs for this purchase cycle.
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'fish_box_purchase_id');
    }

    /**
     * Get sales details connected to this purchase cycle.
     */
    public function salesDetails(): HasMany
    {
        return $this->hasMany(TransactionLineItem::class, 'fish_box_purchase_id');
    }

    /**
     * Scope purchase cycles by broker through the reusable box.
     */
    public function scopeByBroker(Builder $query, int $brokerId): Builder
    {
        return $query->whereHas('fishBox', function ($fishBoxQuery) use ($brokerId) {
            $fishBoxQuery->where('broker_id', $brokerId);
        });
    }

    /**
     * Get the current purchase cycle for a fish box.
     */
    public static function getCurrentForBox(int $fishBoxId, ?int $brokerId = null): ?self
    {
        $query = static::where('fish_box_id', $fishBoxId)->latest('id');

        if ($brokerId) {
            $query->byBroker($brokerId);
        }

        return $query->first();
    }

    /**
     * Create a purchase cycle for a reusable fish box.
     */
    public static function createForBox(
        int $fishBoxId,
        int $fishTypeId,
        ?float $costPrice = null,
        ?int $userId = null,
        ?string $purchaseDate = null
    ): self {
        $purchase = static::create([
            'fish_box_id' => $fishBoxId,
            'fish_type_id' => $fishTypeId,
            'created_by_user_id' => $userId,
            'purchase_date' => $purchaseDate ?: now()->toDateString(),
            'cost_price' => $costPrice,
        ]);

        InventoryMovement::createLogForPurchase(
            $purchase->id,
            FishBoxStatusConstant::IN_STOCK,
            $userId
        );

        return $purchase;
    }
}
