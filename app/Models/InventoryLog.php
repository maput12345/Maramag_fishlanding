<?php

namespace App\Models;

use App\Constants\FishBoxStatusConstant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InventoryLog extends Model
{
    use HasFactory;

    protected $table = 'fish_inventory';

    protected $fillable = [
        'fish_box_purchase_id',
        'created_by_user_id',
        'status',
    ];

    /**
     * Get the purchase cycle for this log entry.
     */
    public function fishBoxPurchase(): BelongsTo
    {
        return $this->belongsTo(FishBoxPurchase::class, 'fish_box_purchase_id');
    }

    /**
     * Get the user who created this inventory log entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Compatibility accessor for older admin views.
     */
    public function getActionAttribute(): string
    {
        return $this->status;
    }

    /**
     * Compatibility accessor for older views that reference the fish box directly.
     */
    public function getFishBoxAttribute(): ?FishBox
    {
        return $this->fishBoxPurchase?->fishBox;
    }

    /**
     * Expose the broker through the related fish box.
     */
    public function getBrokerAttribute(): ?Broker
    {
        return $this->fishBoxPurchase?->fishBox?->broker;
    }

    /**
     * Scope to filter by action.
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('status', $action);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange(Builder $query, ?string $dateFrom = null, ?string $dateTo = null): Builder
    {
        $createdAtColumn = $query->getModel()->qualifyColumn('created_at');

        if ($dateFrom) {
            try {
                $query->where($createdAtColumn, '>=', Carbon::parse($dateFrom)->startOfDay());
            } catch (\Throwable $exception) {
                // Ignore malformed dates so filter behavior remains non-breaking.
            }
        }

        if ($dateTo) {
            try {
                $query->where($createdAtColumn, '<=', Carbon::parse($dateTo)->endOfDay());
            } catch (\Throwable $exception) {
                // Ignore malformed dates so filter behavior remains non-breaking.
            }
        }

        return $query;
    }

    /**
     * Scope to filter by a specific date.
     */
    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->whereBetween($query->getModel()->qualifyColumn('created_at'), [
            Carbon::parse($date)->startOfDay(),
            Carbon::parse($date)->endOfDay(),
        ]);
    }

    /**
     * Create an inventory log for a purchase cycle.
     */
    public static function createLogForPurchase(int $fishBoxPurchaseId, string $status, ?int $userId = null): self
    {
        return static::create([
            'fish_box_purchase_id' => $fishBoxPurchaseId,
            'created_by_user_id' => $userId,
            'status' => $status,
        ]);
    }

    /**
     * Create an inventory log for the current purchase cycle of a fish box.
     */
    public static function createLogForFishBox($fishBoxId, $status, ?int $userId = null): self
    {
        $purchase = FishBoxPurchase::getCurrentForBox((int) $fishBoxId);

        if (!$purchase) {
            throw new \RuntimeException('No purchase cycle found for fish box #' . $fishBoxId);
        }

        return static::createLogForPurchase($purchase->id, $status, $userId);
    }

    /**
     * Get summary statistics for a specific date.
     */
    public static function getSummaryForDate(string $date): array
    {
        $counts = static::query()
            ->byDate($date)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'stocked' => (int) ($counts[FishBoxStatusConstant::IN_STOCK] ?? 0),
            'sold' => (int) ($counts[FishBoxStatusConstant::SOLD] ?? 0),
            'returned' => (int) ($counts[FishBoxStatusConstant::RETURNED] ?? 0),
            'missing' => (int) ($counts[FishBoxStatusConstant::MISSING] ?? 0),
        ];
    }

    /**
     * Get paginated inventory logs with filters.
     */
    public static function getPaginatedWithFilters(
        ?string $action,
        ?string $dateFrom,
        ?string $dateTo,
        int $perPage = 12,
        string $pageName = 'page'
    ): LengthAwarePaginator
    {
        $query = static::with([
                'fishBoxPurchase:id,fish_box_id,fish_type_id',
                'fishBoxPurchase.fishBox' => function ($fishBoxQuery) {
                    $fishBoxQuery->select(['fish_boxes.id', 'fish_boxes.broker_id', 'fish_boxes.qr_code', 'fish_boxes.box_status'])
                        ->withBrokerBoxNumber()
                        ->with([
                            'currentPurchase' => function ($purchaseQuery) {
                                $purchaseQuery->select([
                                    'fish_box_purchases.id',
                                    'fish_box_purchases.fish_box_id',
                                    'fish_box_purchases.fish_type_id',
                                ]);
                            },
                            'currentPurchase.fishType:id,name',
                            'broker:id,first_name,middle_name,last_name,suffix,stall_id,stall_name',
                        ]);
                },
            ])
            ->whereIn('status', [FishBoxStatusConstant::RETURNED, FishBoxStatusConstant::MISSING]);

        if ($action) {
            $query->byAction($action);
        }

        $query->byDateRange($dateFrom, $dateTo);

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Delete sold logs close to the sale creation timestamp.
     */
    public static function deleteLogForFishBox(int $fishBoxId, Carbon $createdAt): int
    {
        $from = $createdAt->copy()->subMinute();
        $to = $createdAt->copy()->addMinute();
        $purchase = FishBoxPurchase::getCurrentForBox($fishBoxId);

        if (!$purchase) {
            return 0;
        }

        return static::where('fish_box_purchase_id', $purchase->id)
            ->where('status', FishBoxStatusConstant::SOLD)
            ->whereBetween('created_at', [$from, $to])
            ->delete();
    }

    /**
     * Get top 5 fish types most sold based on inventory logs.
     */
    public static function getTopFishTypesSold(): Collection
    {
        return static::join('fish_box_purchases', 'fish_inventory.fish_box_purchase_id', '=', 'fish_box_purchases.id')
            ->join('fish_types', 'fish_box_purchases.fish_type_id', '=', 'fish_types.id')
            ->where('fish_inventory.status', FishBoxStatusConstant::SOLD)
            ->selectRaw('fish_types.id, fish_types.name, COUNT(DISTINCT fish_inventory.fish_box_purchase_id) as total_sold')
            ->groupBy('fish_types.id', 'fish_types.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'fish_type' => (object) [
                        'id' => $item->id,
                        'name' => $item->name,
                    ],
                    'sold_count' => $item->total_sold,
                ];
            });
    }

    /**
     * Get top fish types sold with filters for admin analysis.
     */
    public static function getTopFishTypesSoldForAdmin(string $dateFrom, string $dateTo, ?string $status = null, int $limit = 5): Collection
    {
        $query = static::join('fish_box_purchases', 'fish_inventory.fish_box_purchase_id', '=', 'fish_box_purchases.id')
            ->join('fish_types', 'fish_box_purchases.fish_type_id', '=', 'fish_types.id')
            ->where('fish_inventory.status', FishBoxStatusConstant::SOLD)
            ->where('fish_inventory.created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('fish_inventory.created_at', '<=', Carbon::parse($dateTo)->endOfDay());

        return $query->selectRaw('fish_types.id, fish_types.name, COUNT(DISTINCT fish_inventory.fish_box_purchase_id) as total_sold')
            ->groupBy('fish_types.id', 'fish_types.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'fish_type' => (object) [
                        'id' => $item->id,
                        'name' => $item->name,
                    ],
                    'sold_count' => $item->total_sold,
                ];
            });
    }
}
