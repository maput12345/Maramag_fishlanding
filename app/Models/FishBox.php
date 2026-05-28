<?php

namespace App\Models;

use App\Constants\FishBoxStatusConstant;
use App\Constants\SalesStatusConstant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FishBox extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'FishBox';

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
        return $this->hasMany(FishBoxStockCycle::class, 'fish_box_id');
    }

    /**
     * Get the current purchase cycle.
     */
    public function currentPurchase()
    {
        return $this->hasOne(FishBoxStockCycle::class, 'fish_box_id')->latestOfMany();
    }

    /**
     * Get all inventory logs through purchase cycles.
     */
    public function inventoryLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryMovement::class,
            FishBoxStockCycle::class,
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
            TransactionLineItem::class,
            FishBoxStockCycle::class,
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

        $this->resolvedBrokerBoxNumber = static::query()
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
        if ($this->box_status === FishBoxStatusConstant::UNASSIGNED) {
            return null;
        }

        return $this->currentPurchase?->fish_type_id;
    }

    /**
     * Expose the active fish type name from the latest purchase cycle.
     */
    public function getFishTypeNameAttribute(): ?string
    {
        if ($this->box_status === FishBoxStatusConstant::UNASSIGNED) {
            return null;
        }

        return BrokerFishTypeAssignment::resolveDisplayName(
            $this->broker_id,
            $this->currentPurchase?->fishType
        );
    }

    /**
     * Expose the active cost price from the latest purchase cycle.
     */
    public function getCostPriceAttribute(): ?string
    {
        if ($this->box_status === FishBoxStatusConstant::UNASSIGNED) {
            return null;
        }

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
     * Scope retired boxes.
     */
    public function scopeRetired($query)
    {
        return $query->where('box_status', FishBoxStatusConstant::RETIRED);
    }

    /**
     * Select the broker-local box number up front to avoid per-row count lookups.
     */
    public function scopeWithBrokerBoxNumber(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select($query->getModel()->qualifyColumn('*'));
        }

        return $query->selectSub(function ($subQuery) {
            $subQuery->from('FishBox as broker_boxes')
                ->selectRaw('COUNT(*)')
                ->whereColumn('broker_boxes.broker_id', 'FishBox.broker_id')
                ->whereColumn('broker_boxes.id', '<=', 'FishBox.id')
                ->whereNull('broker_boxes.deleted_at');
        }, 'broker_box_number');
    }

    /**
     * Register multiple reusable fish boxes without assigning stock yet.
     */
    public static function createEmptyBoxes(int $quantity, int $brokerId): array
    {
        $createdBoxes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $fishBox = static::create([
                'qr_code' => static::generateUniqueQrCode(),
                'box_status' => FishBoxStatusConstant::UNASSIGNED,
                'broker_id' => $brokerId,
            ]);

            $createdBoxes[] = $fishBox->fresh('currentPurchase.fishType');
        }

        return $createdBoxes;
    }

    /**
     * Build the base broker fish box query with optional filters.
     */
    private static function buildFilteredFishBoxQuery(
        ?string $search = null,
        ?string $status = null,
        ?int $fishTypeId = null,
        ?int $brokerId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Builder {
        $query = static::with(['currentPurchase.fishType', 'broker.user'])
            ->select('FishBox.*')
            ->withBrokerBoxNumber();

        if ($search) {
            $normalizedSearch = preg_replace('/[^0-9]/', '', $search);

            $query->where(function ($q) use ($search, $normalizedSearch) {
                $q->orWhereHas('currentPurchase.fishType', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', '%' . $search . '%');
                });

                if ($normalizedSearch !== '') {
                    $q->orWhereRaw(
                        '(SELECT COUNT(*) FROM FishBox AS broker_boxes WHERE broker_boxes.broker_id = FishBox.broker_id AND broker_boxes.id <= FishBox.id AND broker_boxes.deleted_at IS NULL) = ?',
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

        if ($dateFrom || $dateTo) {
            $query->whereHas('currentPurchase', function ($purchaseQuery) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $purchaseQuery->whereDate('purchase_date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $purchaseQuery->whereDate('purchase_date', '<=', $dateTo);
                }
            });
        }

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        return $query;
    }

    /**
     * Get paginated fish boxes with search and filter functionality.
     */
    public static function getPaginatedWithFilters(?string $search = null, ?string $status = null, ?int $fishTypeId = null, int $perPage = 12, ?int $brokerId = null, ?string $dateFrom = null, ?string $dateTo = null): LengthAwarePaginator
    {
        return static::buildFilteredFishBoxQuery($search, $status, $fishTypeId, $brokerId, $dateFrom, $dateTo)
            ->orderBy('FishBox.created_at', 'desc')
            ->orderBy('FishBox.id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get the full filtered fish box set for broker bulk QR printing.
     *
     * @return Collection<int, array{id:int,name:string,fish_name:string,qr_code:string,status:string}>
     */
    public static function getFilteredForBulkQrPrint(
        ?string $search = null,
        ?string $status = null,
        ?int $fishTypeId = null,
        ?int $brokerId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Collection {
        return static::buildFilteredFishBoxQuery($search, $status, $fishTypeId, $brokerId, $dateFrom, $dateTo)
            ->orderBy('FishBox.created_at', 'desc')
            ->orderBy('FishBox.id', 'desc')
            ->get()
            ->map(static function (self $fishBox): array {
                return [
                    'id' => (int) $fishBox->id,
                    'name' => $fishBox->name,
                    'fish_name' => $fishBox->fish_type_name ?? 'Unassigned',
                    'qr_code' => $fishBox->qr_code,
                    'status' => $fishBox->status,
                ];
            })
            ->values();
    }

    /**
     * Get the latest default cost price for a broker fish type assignment.
     */
    public static function getDefaultCostPriceForBrokerFishType(int $brokerId, int $fishTypeId): ?float
    {
        $assignment = BrokerFishTypeAssignment::query()
            ->select(['id', 'broker_id', 'fish_type_id'])
            ->with([
                'latestPrice' => function ($query) {
                    $query->select([
                        'FishPriceRecord.id',
                        'FishPriceRecord.broker_fish_type_id',
                        'FishPriceRecord.default_cost_price',
                    ]);
                },
            ])
            ->where('broker_id', $brokerId)
            ->where('fish_type_id', $fishTypeId)
            ->first();

        if (!$assignment || $assignment->latestPrice?->default_cost_price === null) {
            return null;
        }

        return (float) $assignment->latestPrice->default_cost_price;
    }

    /**
     * Build a fish-type to default-cost map for broker inventory forms.
     *
     * @return array<string, string>
     */
    public static function getDefaultCostMapForBroker(int $brokerId): array
    {
        return BrokerFishTypeAssignment::query()
            ->select(['id', 'broker_id', 'fish_type_id'])
            ->with([
                'latestPrice' => function ($query) {
                    $query->select([
                        'FishPriceRecord.id',
                        'FishPriceRecord.broker_fish_type_id',
                        'FishPriceRecord.default_cost_price',
                    ]);
                },
            ])
            ->where('broker_id', $brokerId)
            ->get()
            ->filter(fn (BrokerFishTypeAssignment $assignment): bool => $assignment->latestPrice?->default_cost_price !== null)
            ->mapWithKeys(function (BrokerFishTypeAssignment $assignment): array {
                return [
                    (string) $assignment->fish_type_id => (string) $assignment->latestPrice->default_cost_price,
                ];
            })
            ->all();
    }

    /**
     * Count inactive fish boxes that can start a new daily stock cycle.
     */
    public static function countEligibleForBulkRestock(int $brokerId): int
    {
        return static::query()
            ->where('broker_id', $brokerId)
            ->whereIn('box_status', [
                FishBoxStatusConstant::UNASSIGNED,
                FishBoxStatusConstant::RETURNED,
            ])
            ->count();
    }

    /**
     * Get inactive boxes that can be selected for daily restocking.
     */
    public static function getEligibleForBulkRestock(int $brokerId): Collection
    {
        return static::query()
            ->select('FishBox.*')
            ->withBrokerBoxNumber()
            ->with(['currentPurchase.fishType'])
            ->where('broker_id', $brokerId)
            ->whereIn('box_status', [
                FishBoxStatusConstant::UNASSIGNED,
                FishBoxStatusConstant::RETURNED,
            ])
            ->orderBy('FishBox.id')
            ->get();
    }

    /**
     * Create a fresh purchase cycle for each selected reusable box.
     */
    public static function bulkRestock(
        int $brokerId,
        array $fishBoxIds,
        int $fishTypeId,
        float $costPrice,
        int $userId
    ): int {
        $eligibleBoxes = static::query()
            ->where('broker_id', $brokerId)
            ->whereIn('id', $fishBoxIds)
            ->whereIn('box_status', [
                FishBoxStatusConstant::UNASSIGNED,
                FishBoxStatusConstant::RETURNED,
                FishBoxStatusConstant::IN_STOCK,
            ])
            ->orderBy('id')
            ->get();

        if ($eligibleBoxes->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($eligibleBoxes, $fishTypeId, $costPrice, $userId) {
            foreach ($eligibleBoxes as $fishBox) {
                FishBoxStockCycle::createForBox(
                    $fishBox->id,
                    $fishTypeId,
                    $costPrice,
                    $userId
                );

                if ($fishBox->box_status !== FishBoxStatusConstant::IN_STOCK) {
                    $fishBox->update([
                        'box_status' => FishBoxStatusConstant::IN_STOCK,
                    ]);
                }
            }
        });

        return $eligibleBoxes->count();
    }

    /**
     * Get fish boxes available for sale for a broker.
     */
    public static function getAvailableForSale(int $brokerId)
    {
        return static::with('currentPurchase.fishType')
            ->withBrokerBoxNumber()
            ->where('box_status', FishBoxStatusConstant::IN_STOCK)
            ->whereHas('currentPurchase')
            ->where('broker_id', $brokerId)
            ->get();
    }

    /**
     * Get grouped box-status totals for summary cards.
     */
    public static function getStatusSummary(?int $brokerId = null): array
    {
        $counts = static::query()
            ->when($brokerId, function ($query) use ($brokerId) {
                $query->where('broker_id', $brokerId);
            })
            ->selectRaw('box_status, COUNT(*) as total')
            ->groupBy('box_status')
            ->pluck('total', 'box_status');

        $summary = [
            'unassigned' => (int) ($counts[FishBoxStatusConstant::UNASSIGNED] ?? 0),
            'in_stock' => (int) ($counts[FishBoxStatusConstant::IN_STOCK] ?? 0),
            'sold' => (int) ($counts[FishBoxStatusConstant::SOLD] ?? 0),
            'returned' => (int) ($counts[FishBoxStatusConstant::RETURNED] ?? 0),
            'missing' => (int) ($counts[FishBoxStatusConstant::MISSING] ?? 0),
            'retired' => (int) ($counts[FishBoxStatusConstant::RETIRED] ?? 0),
        ];

        $summary['total'] = array_sum($summary);

        return $summary;
    }

    /**
     * Get the current admin tracking snapshot for returned and missing fish boxes.
     */
    public static function getAdminTrackingStatuses(
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 12,
        string $pageName = 'tracking_page'
    ): LengthAwarePaginator {
        $query = static::query()
            ->select('FishBox.*')
            ->withBrokerBoxNumber()
            ->with([
                'currentPurchase' => function ($purchaseQuery) {
                    $purchaseQuery->select([
                        'FishBoxStockCycle.id',
                        'FishBoxStockCycle.fish_box_id',
                        'FishBoxStockCycle.fish_type_id',
                    ]);
                },
                'currentPurchase.fishType:id,name',
                'broker:id,first_name,middle_name,last_name,suffix,stall_name',
            ])
            ->whereIn('box_status', FishBoxStatusConstant::getStatusOnlyForAdmin());

        if ($status) {
            $query->where('box_status', $status);
        }

        if ($dateFrom) {
            try {
                $query->where('FishBox.updated_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            } catch (\Throwable $exception) {
                // Ignore malformed dates so filtering stays non-breaking.
            }
        }

        if ($dateTo) {
            try {
                $query->where('FishBox.updated_at', '<=', Carbon::parse($dateTo)->endOfDay());
            } catch (\Throwable $exception) {
                // Ignore malformed dates so filtering stays non-breaking.
            }
        }

        return $query
            ->orderBy('FishBox.updated_at', 'desc')
            ->orderBy('FishBox.id', 'desc')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Get current missing boxes with broker ownership for the admin dashboard.
     */
    public static function getCurrentMissingBoxes(int $limit = 6)
    {
        return static::query()
            ->select('FishBox.*')
            ->withBrokerBoxNumber()
            ->with([
                'currentPurchase' => function ($purchaseQuery) {
                    $purchaseQuery->select([
                        'FishBoxStockCycle.id',
                        'FishBoxStockCycle.fish_box_id',
                        'FishBoxStockCycle.fish_type_id',
                    ]);
                },
                'currentPurchase.fishType:id,name',
                'broker:id,first_name,middle_name,last_name,suffix,stall_name',
            ])
            ->missing()
            ->orderBy('FishBox.updated_at', 'desc')
            ->orderBy('FishBox.id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Build a driver-safe buyer full-name SQL expression.
     */
    private static function buyerNameExpression(string $table = 'Buyer'): string
    {
        if (static::query()->getConnection()->getDriverName() === 'sqlite') {
            return "TRIM(COALESCE({$table}.first_name, '')"
                . " || CASE WHEN {$table}.middle_name IS NOT NULL AND {$table}.middle_name != '' THEN ' ' || {$table}.middle_name ELSE '' END"
                . " || CASE WHEN {$table}.last_name IS NOT NULL AND {$table}.last_name != '' THEN ' ' || {$table}.last_name ELSE '' END)";
        }

        return "TRIM(CONCAT_WS(' ', {$table}.first_name, {$table}.middle_name, {$table}.last_name))";
    }

    /**
     * Get the broker's current missing fish boxes with the latest buyer name.
     */
    public static function getBrokerMissingTracking(
        int $brokerId,
        ?string $search = null,
        int $perPage = 12,
        string $pageName = 'page'
    ): LengthAwarePaginator {
        $buyerNameExpression = static::buyerNameExpression();

        $query = static::query()
            ->select('FishBox.*')
            ->withBrokerBoxNumber()
            ->selectSub(function ($subQuery) use ($buyerNameExpression) {
                $subQuery->from('TransactionLineItem')
                    ->join('FishBoxStockCycle', 'FishBoxStockCycle.id', '=', 'TransactionLineItem.fish_box_purchase_id')
                    ->join('SalesTransaction', 'SalesTransaction.id', '=', 'TransactionLineItem.sale_id')
                    ->leftJoin('Buyer', 'Buyer.id', '=', 'SalesTransaction.buyer_id')
                    ->selectRaw($buyerNameExpression)
                    ->whereColumn('FishBoxStockCycle.fish_box_id', 'FishBox.id')
                    ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses())
                    ->orderByDesc('TransactionLineItem.id')
                    ->limit(1);
            }, 'last_buyer_name')
            ->with([
                'currentPurchase' => function ($purchaseQuery) {
                    $purchaseQuery->select([
                        'FishBoxStockCycle.id',
                        'FishBoxStockCycle.fish_box_id',
                        'FishBoxStockCycle.fish_type_id',
                    ]);
                },
                'currentPurchase.fishType:id,name',
            ])
            ->where('FishBox.broker_id', $brokerId)
            ->missing();

        if ($search) {
            $search = trim($search);
            $normalizedSearch = preg_replace('/[^0-9]/', '', $search);
            $isPlainBoxNumberSearch = preg_match('/^#?\d+$/', $search) === 1;

            $query->where(function (Builder $trackingQuery) use ($search, $normalizedSearch, $isPlainBoxNumberSearch, $buyerNameExpression) {
                if (! $isPlainBoxNumberSearch) {
                    $trackingQuery->where('FishBox.qr_code', 'like', '%' . $search . '%')
                        ->orWhereHas('currentPurchase.fishType', function ($fishTypeQuery) use ($search) {
                            $fishTypeQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('salesDetails.sale.buyer', function ($buyerQuery) use ($search, $buyerNameExpression) {
                            $buyerQuery->whereRaw("{$buyerNameExpression} like ?", ['%' . $search . '%']);
                        });
                } else {
                    $trackingQuery->whereRaw('1 = 0');
                }

                if ($normalizedSearch !== '') {
                    $trackingQuery->orWhereRaw(
                        '(SELECT COUNT(*) FROM FishBox AS broker_boxes WHERE broker_boxes.broker_id = FishBox.broker_id AND broker_boxes.id <= FishBox.id AND broker_boxes.deleted_at IS NULL) = ?',
                        [(int) $normalizedSearch]
                    )
                    ->orWhere('FishBox.id', (int) $normalizedSearch);
                }
            });
        }

        return $query
            ->orderBy('FishBox.updated_at', 'desc')
            ->orderBy('FishBox.id', 'desc')
            ->paginate($perPage, ['*'], $pageName);
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
                InventoryMovement::createLogForFishBox($fishBox->id, $status, $userId);
            }

            return true;
        }

        $fishBox = static::find($fishBoxId);

        if (!$fishBox) {
            return false;
        }

        $fishBox->update(['box_status' => $status]);
        InventoryMovement::createLogForFishBox($fishBox->id, $status, $userId);

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
            $purchase = FishBoxStockCycle::createForBox(
                $this->id,
                (int) $data['fish_type_id'],
                isset($data['cost_price']) ? (float) $data['cost_price'] : null,
                $userId
            );
        }

        if (isset($data['status']) && $data['status'] !== $originalStatus) {
            $this->update(['box_status' => $data['status']]);
            InventoryMovement::createLogForFishBox($this->id, $data['status'], $userId);
        }

        return true;
    }

    /**
     * Determine whether the active purchase cycle already has recorded SalesTransaction.
     */
    public function currentPurchaseHasSalesHistory(): bool
    {
        if (!$this->currentPurchase) {
            return false;
        }

        return $this->currentPurchase->salesDetails()->exists();
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
            $boxIds = array_values(array_filter(
                array_unique(array_map('intval', $boxIds)),
                fn (int $boxId): bool => $boxId > 0
            ));

            if (empty($boxIds)) {
                continue;
            }

            $ownedBoxIds = static::query()
                ->where('broker_id', $brokerId)
                ->whereIn('id', $boxIds)
                ->pluck('id')
                ->map(fn ($boxId): int => (int) $boxId)
                ->all();

            if (!empty($ownedBoxIds)) {
                self::updateStatus($ownedBoxIds, FishBoxStatusConstant::SOLD, $userId);
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
     * Automatically mark still-sold fish boxes as missing after the cutoff.
     */
    public static function markSoldBoxesMissingAtCutoff(?Carbon $cutoff = null, ?int $userId = null): int
    {
        $cutoff ??= now(config('app.timezone'))->setTime(11, 59, 0);

        $eligibleBoxIds = static::query()
            ->sold()
            ->where('updated_at', '<=', $cutoff)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (empty($eligibleBoxIds)) {
            return 0;
        }

        static::updateStatus($eligibleBoxIds, FishBoxStatusConstant::MISSING, $userId);

        return count($eligibleBoxIds);
    }

    /**
     * Get a fish box by QR code and broker.
     */
    public static function getFishBoxByQrCode(string $qrCode, int $brokerId): ?self
    {
        return static::with('currentPurchase.fishType')
            ->withBrokerBoxNumber()
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
            ->withBrokerBoxNumber()
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
        $latestDetail = $this->salesDetails()->with('sale.buyer')->latest('TransactionLineItem.id')->first();
        $latestSale = $latestDetail?->sale;

        return $latestSale ? $latestSale->buyer_contact : null;
    }

    /**
     * Get buyer name for the latest sale of this fish box.
     */
    public function getBuyerNamesAttribute()
    {
        $latestDetail = $this->salesDetails()->with('sale.buyer')->latest('TransactionLineItem.id')->first();
        $latestSale = $latestDetail?->sale;

        return $latestSale ? $latestSale->buyer_name : null;
    }

    /**
     * Expose the latest buyer name when it is selected in the base query.
     */
    public function getLastBuyerNameAttribute(): ?string
    {
        if (array_key_exists('last_buyer_name', $this->attributes)) {
            return $this->attributes['last_buyer_name'] ?: null;
        }

        return $this->buyer_names;
    }

    /**
     * Check if the fish box can be marked as missing.
     */
    public function canBeMarkedAsMissing(): bool
    {
        return $this->currentPurchase !== null && !in_array($this->status, [
            FishBoxStatusConstant::IN_STOCK,
            FishBoxStatusConstant::MISSING,
            FishBoxStatusConstant::RETURNED,
            FishBoxStatusConstant::RETIRED,
        ], true);
    }

    /**
     * Check if the fish box can be returned.
     */
    public function canBeReturned(): bool
    {
        return in_array($this->status, [
            FishBoxStatusConstant::SOLD,
            FishBoxStatusConstant::MISSING,
        ], true);
    }

    /**
     * Check if the fish box can be edited.
     */
    public function canBeEdited(): bool
    {
        return !in_array($this->status, [
            FishBoxStatusConstant::SOLD,
            FishBoxStatusConstant::RETIRED,
        ], true) && $this->currentPurchase !== null;
    }

    /**
     * Determine whether the fish box has any audit/history records.
     */
    public function hasAuditHistory(): bool
    {
        return $this->purchases()->exists()
            || $this->inventoryLogs()->exists()
            || $this->salesDetails()->exists();
    }

    /**
     * Check if the fish box can be hard-deleted.
     */
    public function canBeDeleted(): bool
    {
        return !$this->hasAuditHistory();
    }

    /**
     * Check if the fish box can be retired instead of deleted.
     */
    public function canBeRetired(): bool
    {
        return $this->hasAuditHistory()
            && in_array($this->status, [
                FishBoxStatusConstant::UNASSIGNED,
                FishBoxStatusConstant::RETURNED,
                FishBoxStatusConstant::MISSING,
            ], true);
    }

    /**
     * Retired fish boxes are permanent because they represent boxes that
     * should no longer be used, such as damaged physical boxes.
     */
    public function canBeRestored(): bool
    {
        return false;
    }

    /**
     * Clear returned fish boxes so they are ready for a new stock cycle.
     */
    public static function returnAllToStock(int $brokerId, ?int $userId = null): int
    {
        $returnedFishBoxes = static::returned()
            ->where('broker_id', $brokerId)
            ->get();

        if ($returnedFishBoxes->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($returnedFishBoxes as $fishBox) {
            $fishBox->update(['box_status' => FishBoxStatusConstant::UNASSIGNED]);
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
