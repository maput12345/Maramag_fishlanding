<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'fish_box_purchase_id',
        'unit_price',
        'sub_total',
        'discount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Get the sale that this sales detail belongs to.
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    /**
     * Alias for singular naming.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    /**
     * Get the purchase cycle sold on this row.
     */
    public function fishBoxPurchase(): BelongsTo
    {
        return $this->belongsTo(FishBoxPurchase::class, 'fish_box_purchase_id');
    }

    /**
     * Compatibility accessor that returns the physical fish box.
     */
    public function getFishBoxAttribute(): ?FishBox
    {
        return $this->fishBoxPurchase?->fishBox;
    }

    /**
     * Compatibility helper that mimics the old multiple-box collection.
     */
    public function fishBoxes()
    {
        return $this->fishBox ? collect([$this->fishBox]) : collect();
    }

    /**
     * Get the first fish box for backward compatibility.
     */
    public function getFirstFishBoxAttribute(): ?FishBox
    {
        return $this->fishBox;
    }

    /**
     * Compatibility accessor for the old JSON box_id field.
     */
    public function getBoxIdAttribute(): array
    {
        return $this->fishBoxPurchase?->fish_box_id ? [$this->fishBoxPurchase->fish_box_id] : [];
    }

    /**
     * Get all fish box IDs as an array.
     */
    public function getBoxIdsAttribute(): array
    {
        return $this->box_id;
    }

    /**
     * Get the count of fish boxes on this row.
     */
    public function getBoxCountAttribute(): int
    {
        return $this->fish_box_purchase_id ? 1 : 0;
    }

    /**
     * Derive the item name from the related fish type.
     */
    public function getItemAttribute(): string
    {
        return $this->fishBoxPurchase?->fishType?->name ?? '';
    }

    /**
     * Derive the item description from the related fish type.
     */
    public function getItemDescriptionAttribute(): ?string
    {
        return $this->fishBoxPurchase?->fishType?->description;
    }

    /**
     * Each normalized sales detail represents one fish box.
     */
    public function getQuantityAttribute(): int
    {
        return $this->fish_box_purchase_id ? 1 : 0;
    }

    /**
     * Check if a specific physical fish box matches this row.
     */
    public function hasBoxId(int $boxId): bool
    {
        return (int) ($this->fishBoxPurchase?->fish_box_id) === $boxId;
    }

    /**
     * Create normalized sales detail rows for a sale.
     */
    public static function createSalesDetails(int $saleId, int $brokerId, array $details, array $purchaseIdsByBoxId = []): void
    {
        foreach ($details as $detail) {
            $boxIds = is_array($detail['box_id'] ?? null) ? $detail['box_id'] : [$detail['box_id'] ?? null];
            $boxIds = array_values(array_filter(array_unique($boxIds)));

            if (empty($boxIds)) {
                continue;
            }

            $unitPrice = (float) ($detail['unit_price'] ?? 0);
            $lineSubTotal = (float) ($detail['sub_total'] ?? ($unitPrice * count($boxIds)));
            $perBoxSubTotal = count($boxIds) > 0 ? round($lineSubTotal / count($boxIds), 2) : $unitPrice;
            $perBoxDiscount = max(0, round($unitPrice - $perBoxSubTotal, 2));

            foreach ($boxIds as $boxId) {
                $purchaseId = $purchaseIdsByBoxId[(int) $boxId] ?? null;
                $purchase = $purchaseId
                    ? FishBoxPurchase::query()->whereKey($purchaseId)->first()
                    : FishBoxPurchase::getCurrentForBox((int) $boxId, $brokerId);

                if (!$purchase) {
                    continue;
                }

                self::create([
                    'sale_id' => $saleId,
                    'fish_box_purchase_id' => $purchase->id,
                    'unit_price' => $unitPrice,
                    'sub_total' => $perBoxSubTotal,
                    'discount' => $perBoxDiscount,
                ]);
            }
        }
    }
}
