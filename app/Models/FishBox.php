<?php

namespace App\Models;

use App\Constants\FishBoxStatusConstant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FishBox extends Model
{
    use HasFactory, SoftDeletes;

    protected ?int $resolvedBrokerBoxNumber = null;

    protected $fillable = [
        'qr_code',
        'box_status',
        'broker_id',
    ];

    protected $casts = [
        'qr_code' => 'string',
    ];

    protected $appends = [
        'name',
        'broker_box_number',
        'buyer_contacts',
        'buyer_names',
        'status',
        'fish_type_id',
        'cost_price',
        'fish_type_name',
    ];

    /**
     * Get the broker that owns the fish box.
     */
    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    /**
     * Get all purchase cycles for this reusable fish box.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(FishBoxPurchase::class, 'fish_box_id');
    }

    /**
     * Get the current purchase cycle.
     */
    public function currentPurchase()
    {
        return $this->hasOne(FishBoxPurchase::class, 'fish_box_id')->latestOfMany();
    }

    /**
     * Get all inventory logs through purchase cycles.
     */
    public function inventoryLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryLog::class,
            FishBoxPurchase::class,
            'fish_box_id',
            'fish_box_purchase_id',
            'id',
            'id'
        );
    }

    /**
     * Get all sale details through purchase cycles.
     */
    public function salesDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            SalesDetails::class,
            FishBoxPurchase::class,
            'fish_box_id',
            'fish_box_purchase_id',
            'id',
            'id'
        );
    }

    /**
     * Compatibility name accessor for UI cards and dropdowns.
     */
    public function getNameAttribute(): string
    {
        $boxNumber = $this->broker_box_number ?? $this->id;

        return 'Fish Box #' . str_pad((string) $boxNumber, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get the broker-local running box number for display.
     */
    public function getBrokerBoxNumberAttribute(): ?int
    {
        if (array_key_exists('broker_box_number', $this->attributes)) {
            return (int) $this->attributes['broker_box_number'];
        }

        if ($this->resolvedBrokerBoxNumber !== null) {
            return $this->resolvedBrokerBoxNumber;
        }

        if (!$this->exists || !$this->broker_id) {
            return null;
        }

        $this->resolvedBrokerBoxNumber = static::withTrashed()
            ->where('broker_id', $this->broker_id)
            ->where('id', '<=', $this->id)
            ->count();

        return $this->resolvedBrokerBoxNumber;
    }

    /**
     * Compatibility status accessor.
     */
    public function getStatusAttribute(): string
    {
        return $this->box_status;
    }

    /**
     * Expose the active fish type ID from the latest purchase cycle.
     */
    public function getFishTypeIdAttribute(): ?int
    {
        return $this->currentPurchase?->fish_type_id;
    }

    /**
     * Expose the active fish type name from the latest purchase cycle.
     */
    public function getFishTypeNameAttribute(): ?string
    {
        return $this->currentPurchase?->fishType?->name;
    }

    /**
     * Expose the active cost price from the latest purchase cycle.
     */
    public function getCostPriceAttribute(): ?string
    {
        return $this->currentPurchase?->cost_price;
    }

    /**
     * Scope returned boxes.
     */
    public function scopeReturned($query)
    {
        return $query->where('box_status', FishBoxStatusConstant::RETURNED);
    }

    /**
     * Scope sold boxes.
     */
    public function scopeSold($query)
    {
        return $query->where('box_status', FishBoxStatusConstant::SOLD);
    }

    /**
     * Scope in-stock boxes.
     */
    public function scopeInStock($query)
    {
        return $query->where('box_status', FishBoxStatusConstant::IN_STOCK);
    }

    /**
     * Scope missing boxes.
     */
    public function scopeMissing($query)
    {
        return $query->where('box_status', FishBoxStatusConstant::MISSING);
    }

    /**
     * Create multiple reusable fish boxes and their first purchase cycle.
     */
    public static function createFishBoxes($fishTypeId, $quantity, $brokerId, ?float $costPrice = null, ?int $userId = null): array
    {
        $createdBoxes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $fishBox = static::create([
                'qr_code' => static::generateUniqueQrCode(),
                'box_status' => FishBoxStatusConstant::IN_STOCK,
                'broker_id' => $brokerId,
            ]);

            FishBoxPurchase::createForBox($fishBox->id, (int) $fishTypeId, $costPrice, $userId);
            $createdBoxes[] = $fishBox->fresh('currentPurchase.fishType');
        }

        return $createdBoxes;
    }

    /**
     * Get paginated fish boxes with search and filter functionality.
     */
    public static function getPaginatedWithFilters(?string $search = null, ?string $status = null, ?int $fishTypeId = null, int $perPage = 12, ?int $brokerId = null): LengthAwarePaginator
    {
        $query = static::with(['currentPurchase.fishType', 'broker.user'])
            ->select('fish_boxes.*');

        if ($search) {
            $normalizedSearch = preg_replace('/[^0-9]/', '', $search);

            $query->where(function ($q) use ($search, $normalizedSearch) {
                $q->orWhereHas('currentPurchase.fishType', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', '%' . $search . '%');
                });

                if ($normalizedSearch !== '') {
                    $q->orWhereRaw(
                        '(SELECT COUNT(*) FROM fish_boxes AS broker_boxes WHERE broker_boxes.broker_id = fish_boxes.broker_id AND broker_boxes.id <= fish_boxes.id) = ?',
                        [(int) $normalizedSearch]
                    );
                }
            });
        }

        if ($status) {
            $query->where('box_status', $status);
        }

        if ($fishTypeId) {
            $query->whereHas('currentPurchase', function ($purchaseQuery) use ($fishTypeId) {
                $purchaseQuery->where('fish_type_id', $fishTypeId);
            });
        }

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get fish boxes available for sale for a broker.
     */
    public static function getAvailableForSale(int $brokerId)
    {
        return static::with('currentPurchase.fishType')
            ->where('box_status', FishBoxStatusConstant::IN_STOCK)
            ->where('broker_id', $brokerId)
            ->get();
    }

    /**
     * Update the box status and log the inventory movement.
     */
    public static function updateStatus($fishBoxId, string $status, ?int $userId = null): bool
    {
        if (is_array($fishBoxId)) {
            $fishBoxes = static::whereIn('id', $fishBoxId)->get();

            if ($fishBoxes->isEmpty()) {
                return false;
            }

            foreach ($fishBoxes as $fishBox) {
                $fishBox->update(['box_status' => $status]);
                InventoryLog::createLogForFishBox($fishBox->id, $status, $userId);
            }

            return true;
        }

        $fishBox = static::find($fishBoxId);

        if (!$fishBox) {
            return false;
        }

        $fishBox->update(['box_status' => $status]);
        InventoryLog::createLogForFishBox($fishBox->id, $status, $userId);

        return true;
    }

    /**
     * Update the current purchase cycle and optional box status.
     */
    public function updateBoxAndPurchase(array $data, ?int $userId = null): bool
    {
        $originalStatus = $this->status;
        $purchase = $this->currentPurchase;

        if ($purchase) {
            $purchase->update([
                'fish_type_id' => $data['fish_type_id'] ?? $purchase->fish_type_id,
                'cost_price' => $data['cost_price'] ?? $purchase->cost_price,
            ]);
        } elseif (isset($data['fish_type_id'])) {
            $purchase = FishBoxPurchase::createForBox(
                $this->id,
                (int) $data['fish_type_id'],
                isset($data['cost_price']) ? (float) $data['cost_price'] : null,
                $userId
            );
        }

        if (isset($data['status']) && $data['status'] !== $originalStatus) {
            $this->update(['box_status' => $data['status']]);
            InventoryLog::createLogForFishBox($this->id, $data['status'], $userId);
        }

        return true;
    }

    /**
     * Update fish boxes status for sold sales details.
     */
    public static function updateFishBoxesForSales(int $brokerId, array $salesDetails, int $userId): void
    {
        if (empty($salesDetails)) {
            return;
        }

        foreach ($salesDetails as $detail) {
            $boxIds = is_array($detail['box_id'] ?? null) ? $detail['box_id'] : [$detail['box_id'] ?? null];
            $boxIds = array_filter($boxIds);

            if (!empty($boxIds)) {
                self::updateStatus($boxIds, FishBoxStatusConstant::SOLD, $userId);
            }
        }
    }

    /**
     * Return a sold fish box.
     */
    public static function updateFishBoxesForReturned(int $fishBoxId, int $userId): self
    {
        $fishBox = static::findOrFail($fishBoxId);
        self::updateStatus($fishBox->id, FishBoxStatusConstant::RETURNED, $userId);

        return $fishBox->refresh();
    }

    /**
     * Get a fish box by QR code and broker.
     */
    public static function getFishBoxByQrCode(string $qrCode, int $brokerId): ?self
    {
        return static::with('currentPurchase.fishType')
            ->where('qr_code', $qrCode)
            ->where('broker_id', $brokerId)
            ->first();
    }

    /**
     * Get a fish box by ID with broker validation.
     */
    public static function getFishBoxByIdAndBroker(int $id, int $brokerId): self
    {
        return static::with('currentPurchase.fishType')
            ->where('id', $id)
            ->where('broker_id', $brokerId)
            ->firstOrFail();
    }

    /**
     * Count sold fish boxes.
     */
    public static function getTotalFishBoxes(?int $brokerId): int
    {
        $query = static::where('box_status', FishBoxStatusConstant::SOLD);

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        return $query->count();
    }

    /**
     * Get buyer contact for the latest sale of this fish box.
     */
    public function getBuyerContactsAttribute()
    {
        $latestDetail = $this->salesDetails()->with('sale.buyer')->latest('sales_details.id')->first();
        $latestSale = $latestDetail?->sale;

        return $latestSale ? $latestSale->buyer_contact : null;
    }

    /**
     * Get buyer name for the latest sale of this fish box.
     */
    public function getBuyerNamesAttribute()
    {
        $latestDetail = $this->salesDetails()->with('sale.buyer')->latest('sales_details.id')->first();
        $latestSale = $latestDetail?->sale;

        return $latestSale ? $latestSale->buyer_name : null;
    }

    /**
     * Check if the fish box can be marked as missing.
     */
    public function canBeMarkedAsMissing(): bool
    {
        return !in_array($this->status, [
            FishBoxStatusConstant::IN_STOCK,
            FishBoxStatusConstant::MISSING,
            FishBoxStatusConstant::RETURNED,
        ], true);
    }

    /**
     * Check if the fish box can be returned.
     */
    public function canBeReturned(): bool
    {
        return !in_array($this->status, [
            FishBoxStatusConstant::IN_STOCK,
            FishBoxStatusConstant::MISSING,
            FishBoxStatusConstant::RETURNED,
        ], true);
    }

    /**
     * Check if the fish box can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status !== FishBoxStatusConstant::SOLD;
    }

    /**
     * Check if the fish box can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return !in_array($this->status, [
            FishBoxStatusConstant::SOLD,
            FishBoxStatusConstant::RETURNED,
        ], true);
    }

    /**
     * Return all returned fish boxes to in-stock status for a broker.
     */
    public static function returnAllToStock(int $brokerId): int
    {
        $returnedFishBoxes = static::returned()
            ->where('broker_id', $brokerId)
            ->get();

        if ($returnedFishBoxes->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($returnedFishBoxes as $fishBox) {
            self::updateStatus($fishBox->id, FishBoxStatusConstant::IN_STOCK);
            $count++;
        }

        return $count;
    }

    /**
     * Generate a unique QR code.
     */
    protected static function generateUniqueQrCode(): string
    {
        do {
            $qrCode = Str::uuid()->toString();
            $exists = static::withTrashed()->where('qr_code', $qrCode)->exists();
        } while ($exists);

        return $qrCode;
    }
}
