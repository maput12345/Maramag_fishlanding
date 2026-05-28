<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Constants\SalesStatusConstant;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Constants\FishBoxStatusConstant;

class SalesTransaction extends Model
{
    use HasFactory;

    protected $table = 'SalesTransaction';

    protected $fillable = [
        'sales_date',
        'broker_id',
        'created_by_user_id',
        'buyer_id',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'sales_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    /**
     * @return BelongsTo
     */
    public function broker() : BelongsTo
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    /**
     * @return BelongsTo
     */
    public function buyer() : BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany
     */
    public function salesDetails() : HasMany
    {
        return $this->hasMany(TransactionLineItem::class, 'sale_id');
    }

    /**
     * @return HasMany
     */
    public function salesPayments() : HasMany
    {
        return $this->hasMany(PaymentRecord::class, 'sale_id');
    }

    // Scopes
    /**
     * Scope a query to only include active sales
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', SalesStatusConstant::getAllActiveStatuses());
    }

    // Helper methods
    /**
     * @return float
     */
    public function getBuyerNameAttribute(): ?string
    {
        return $this->buyer?->name;
    }

    /**
     * @return string|null
     */
    public function getBuyerContactAttribute(): ?string
    {
        return $this->buyer?->contact;
    }

    /**
     * Compatibility accessor for the removed remarks column.
     */
    public function getRemarksAttribute(): ?string
    {
        return null;
    }

    /**
     * @return float
     */
    public function getPaidAmountAttribute() : float
    {
        if (array_key_exists('paid_amount_total', $this->attributes)) {
            return (float) ($this->attributes['paid_amount_total'] ?? 0);
        }

        if ($this->relationLoaded('salesPayments')) {
            return (float) $this->salesPayments->sum('paid_amount');
        }

        return (float) $this->salesPayments()->sum('paid_amount');
    }

    /**
     * @return float
     */
    public function getRemainingAmountAttribute() : float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    /**
     * @return void
     */
    public function updatePaymentStatus() : void
    {
        if ($this->paid_amount <= 0) {
            $this->status = SalesStatusConstant::ACTIVE;
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->status = SalesStatusConstant::PAID;
        } else {
            $this->status = SalesStatusConstant::PARTIALLY_PAID;
        }
        $this->save();
    }

    /**
     * @return void
     */
    public function updatePaidAmount() : void
    {
        $this->refresh();
    }

    /**
     * Create a new sales record with details and update fish box status
     *
     * @param array $salesData
     * @param array $salesDetails
     * @param int $brokerId
     * @param array|null $initialPayment
     * @return SalesTransaction
     */
    public static function createSalesWithDetails(array $salesData, array $salesDetails, int $brokerId, ?array $initialPayment = null): SalesTransaction
    {
        return DB::transaction(function () use ($salesData, $salesDetails, $brokerId, $initialPayment) {
            $userId = Auth::id();
            $trustedSale = static::calculateTrustedSaleAmounts($salesDetails, $brokerId);
            $salesDetails = $trustedSale['details'];

            if (!empty($initialPayment['paid_amount']) && (float) $initialPayment['paid_amount'] > $trustedSale['total_amount']) {
                throw ValidationException::withMessages([
                    'initial_paid_amount' => 'Initial payment cannot exceed the total sale amount.',
                ]);
            }

            $buyer = Buyer::resolveForSaleParts(
                $salesData['buyer_first_name'],
                $salesData['buyer_middle_name'] ?? null,
                $salesData['buyer_last_name'],
                $salesData['buyer_contact'] ?? null,
                $brokerId,
                $salesData['buyer_id'] ?? null
            );
            $salesDetails = static::assignAutomaticFishBoxes($salesDetails, $brokerId);
            static::ensureFishBoxesMatchSaleDetails($salesDetails, $brokerId);
            $purchaseIdsByBoxId = static::resolveSellablePurchaseIds(
                static::lockFishBoxesForUpdate($brokerId, static::extractRequestedBoxIds($salesDetails)),
                static::extractRequestedBoxIds($salesDetails)
            );

            $sale = self::create([
                'sales_date' => $salesData['sales_date'],
                'broker_id' => $brokerId,
                'created_by_user_id' => $userId,
                'buyer_id' => $buyer->id,
                'total_amount' => $trustedSale['total_amount'],
                'status' => SalesStatusConstant::ACTIVE
            ]);

            TransactionLineItem::createSalesDetails($sale->id, $brokerId, $salesDetails, $purchaseIdsByBoxId);
            FishBox::updateFishBoxesForSales($brokerId, $salesDetails, $userId);

            if (!empty($initialPayment['paid_amount'])) {
                PaymentRecord::create([
                    'sale_id' => $sale->id,
                    'paid_amount' => $initialPayment['paid_amount'],
                    'payment_date' => $initialPayment['payment_date'],
                    'payment_method' => $initialPayment['payment_method'],
                ]);

                $sale->updatePaidAmount();
                $sale->updatePaymentStatus();
            }

            return $sale->load(['buyer', 'salesDetails.fishBoxPurchase.fishType', 'salesPayments']);
        });
    }

    /**
     * Update an existing sales record with details and update fish box status
     *
     * @param SalesTransaction $sale
     * @param array $salesData
     * @param array $salesDetails
     * @param int $brokerId
     * @return void
     */
    public static function updateSalesWithDetails(SalesTransaction $sale, array $salesData, array $salesDetails, int $brokerId): void
    {
        DB::transaction(function () use ($sale, $salesData, $salesDetails, $brokerId) {
            $userId = Auth::id();
            $trustedSale = static::calculateTrustedSaleAmounts($salesDetails, $brokerId);
            $salesDetails = $trustedSale['details'];
            $buyer = Buyer::resolveForSaleParts(
                $salesData['buyer_first_name'],
                $salesData['buyer_middle_name'] ?? null,
                $salesData['buyer_last_name'],
                $salesData['buyer_contact'] ?? null,
                $brokerId,
                $salesData['buyer_id'] ?? null
            );
            $lockedSale = self::query()
                ->with('salesDetails.fishBoxPurchase')
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existingBoxIds = $lockedSale->salesDetails
                ->map(fn (TransactionLineItem $detail): ?int => $detail->fishBoxPurchase?->fish_box_id ? (int) $detail->fishBoxPurchase->fish_box_id : null)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $requestedBoxIds = static::extractRequestedBoxIds($salesDetails);
            $lockedFishBoxes = static::lockFishBoxesForUpdate(
                $brokerId,
                array_values(array_unique(array_merge($existingBoxIds, $requestedBoxIds)))
            );

            $lockedSale->update([
                'sales_date' => $salesData['sales_date'],
                'buyer_id' => $buyer->id,
                'total_amount' => $trustedSale['total_amount'],
            ]);

            foreach ($lockedSale->salesDetails as $detail) {
                $fishBoxId = $detail->fishBoxPurchase?->fish_box_id;

                if ($fishBoxId) {
                    FishBox::updateStatus($fishBoxId, FishBoxStatusConstant::IN_STOCK, $userId);
                    InventoryMovement::deleteLogForFishBox($fishBoxId, $lockedSale->created_at);

                    $lockedFishBox = $lockedFishBoxes->get((int) $fishBoxId);

                    if ($lockedFishBox) {
                        $lockedFishBox->box_status = FishBoxStatusConstant::IN_STOCK;
                    }
                }
            }

            $lockedSale->salesDetails()->delete();
            static::ensureFishBoxesMatchSaleDetails($salesDetails, $brokerId);
            $purchaseIdsByBoxId = static::resolveSellablePurchaseIds($lockedFishBoxes, $requestedBoxIds);

            TransactionLineItem::createSalesDetails($lockedSale->id, $brokerId, $salesDetails, $purchaseIdsByBoxId);
            FishBox::updateFishBoxesForSales($brokerId, $salesDetails, $userId);
            $lockedSale->refresh();
            $lockedSale->updatePaymentStatus();
        });
    }

    /**
     * Rebuild sale prices and totals on the server so browser-submitted money fields
     * cannot change the official transaction amount.
     *
     * @return array{details: array<int, array<string, mixed>>, total_amount: float}
     */
    public static function calculateTrustedSaleAmounts(array $salesDetails, int $brokerId): array
    {
        $fishTypeIds = collect($salesDetails)
            ->filter(fn ($detail): bool => is_array($detail))
            ->pluck('fish_type_id')
            ->filter(fn ($fishTypeId): bool => $fishTypeId !== null && $fishTypeId !== '')
            ->map(fn ($fishTypeId): int => (int) $fishTypeId)
            ->unique()
            ->values();

        if ($fishTypeIds->isEmpty()) {
            throw ValidationException::withMessages([
                'sales_details' => 'Please add at least one sale item.',
            ]);
        }

        $priceMap = BrokerFishTypeAssignment::query()
            ->select(['id', 'broker_id', 'fish_type_id'])
            ->with([
                'latestPrice' => function ($query) {
                    $query->select([
                        'FishPriceRecord.id',
                        'FishPriceRecord.broker_fish_type_id',
                        'FishPriceRecord.price',
                    ]);
                },
            ])
            ->where('broker_id', $brokerId)
            ->whereIn('fish_type_id', $fishTypeIds->all())
            ->get()
            ->filter(fn (BrokerFishTypeAssignment $assignment): bool => $assignment->latestPrice !== null)
            ->mapWithKeys(fn (BrokerFishTypeAssignment $assignment): array => [
                (int) $assignment->fish_type_id => round((float) $assignment->latestPrice->price, 2),
            ]);

        $trustedDetails = [];
        $trustedTotal = 0.0;

        foreach ($salesDetails as $index => $detail) {
            if (!is_array($detail)) {
                continue;
            }

            $fishTypeId = (int) ($detail['fish_type_id'] ?? 0);
            $unitPrice = $priceMap->get($fishTypeId)
                ?? static::parseMoneyValue($detail['unit_price'] ?? null);

            if ($fishTypeId <= 0 || $unitPrice === null || $unitPrice <= 0) {
                throw ValidationException::withMessages([
                    "sales_details.{$index}.unit_price" => 'Enter a valid price for the selected fish before saving the sale.',
                ]);
            }

            $boxIds = collect($detail['box_id'] ?? [])
                ->filter(fn ($boxId): bool => $boxId !== null && $boxId !== '')
                ->values()
                ->all();
            $quantity = count($boxIds) > 0
                ? count($boxIds)
                : max(1, (int) ($detail['quantity'] ?? 1));
            $discount = static::calculateTrustedUnitDiscount($detail, $unitPrice);
            $unitSubTotal = max(0, round($unitPrice - $discount, 2));
            $lineSubTotal = round($unitSubTotal * $quantity, 2);

            $detail['unit_price'] = number_format($unitPrice, 2, '.', '');
            $detail['discount'] = number_format($discount, 2, '.', '');
            $detail['sub_total'] = number_format($lineSubTotal, 2, '.', '');
            $detail['quantity'] = $quantity;

            $trustedDetails[] = $detail;
            $trustedTotal += $lineSubTotal;
        }

        return [
            'details' => $trustedDetails,
            'total_amount' => round($trustedTotal, 2),
        ];
    }

    private static function calculateTrustedUnitDiscount(array $detail, float $unitPrice): float
    {
        $discountMode = $detail['discount_mode'] ?? 'percent';
        $discountValue = static::parseMoneyValue($detail['discount_value'] ?? null);

        if ($discountValue === null) {
            $discountValue = $discountMode === 'amount'
                ? static::parseMoneyValue($detail['discount'] ?? null)
                : static::parseMoneyValue($detail['discount_percent'] ?? null);
        }

        $discountValue = $discountValue ?? 0.0;

        if ($discountMode === 'amount') {
            return round(min($unitPrice, max(0, $discountValue)), 2);
        }

        $discountPercent = min(100, max(0, $discountValue));

        return round($unitPrice * ($discountPercent / 100), 2);
    }

    private static function parseMoneyValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    /**
     * Extract a unique list of requested physical fish box IDs from sale details.
     *
     * @return array<int, int>
     */
    private static function extractRequestedBoxIds(array $salesDetails): array
    {
        return collect($salesDetails)
            ->flatMap(function ($detail): array {
                if (!is_array($detail)) {
                    return [];
                }

                $boxIds = $detail['box_id'] ?? [];

                return is_array($boxIds) ? $boxIds : [$boxIds];
            })
            ->filter(fn ($boxId): bool => $boxId !== null && $boxId !== '')
            ->map(fn ($boxId): int => (int) $boxId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Prevent crafted requests from pricing a selected physical box under a
     * different fish type row.
     */
    private static function ensureFishBoxesMatchSaleDetails(array $salesDetails, int $brokerId): void
    {
        $requestedBoxIds = static::extractRequestedBoxIds($salesDetails);

        if ($requestedBoxIds === []) {
            return;
        }

        $boxes = FishBox::query()
            ->with([
                'currentPurchase' => function ($query) {
                    $query->select([
                        'FishBoxStockCycle.id',
                        'FishBoxStockCycle.fish_box_id',
                        'FishBoxStockCycle.fish_type_id',
                    ]);
                },
            ])
            ->where('broker_id', $brokerId)
            ->whereIn('id', $requestedBoxIds)
            ->get()
            ->keyBy('id');

        foreach ($salesDetails as $index => $detail) {
            if (!is_array($detail)) {
                continue;
            }

            $fishTypeId = (int) ($detail['fish_type_id'] ?? 0);
            $boxIds = collect($detail['box_id'] ?? [])
                ->filter(fn ($boxId): bool => $boxId !== null && $boxId !== '')
                ->map(fn ($boxId): int => (int) $boxId);

            foreach ($boxIds as $boxId) {
                $box = $boxes->get($boxId);

                if (!$box || (int) ($box->currentPurchase?->fish_type_id ?? 0) !== $fishTypeId) {
                    throw ValidationException::withMessages([
                        "sales_details.{$index}.fish_type_id" => 'One or more selected fish boxes do not match the selected fish type. Refresh the sales form and try again.',
                    ]);
                }
            }
        }
    }

    /**
     * Fill manual sale rows with available fish boxes at save time.
     */
    private static function assignAutomaticFishBoxes(array $salesDetails, int $brokerId): array
    {
        $reservedBoxIds = static::extractRequestedBoxIds($salesDetails);

        foreach ($salesDetails as $index => $detail) {
            if (!is_array($detail)) {
                continue;
            }

            $existingBoxIds = collect($detail['box_id'] ?? [])
                ->filter(fn ($boxId): bool => $boxId !== null && $boxId !== '')
                ->map(fn ($boxId): int => (int) $boxId)
                ->values()
                ->all();

            if ($existingBoxIds !== []) {
                continue;
            }

            $fishTypeId = (int) ($detail['fish_type_id'] ?? 0);
            $quantity = max(1, (int) ($detail['quantity'] ?? 1));

            if ($fishTypeId <= 0) {
                continue;
            }

            $availableBoxes = FishBox::query()
                ->with([
                    'currentPurchase' => function ($query) {
                        $query->select([
                            'FishBoxStockCycle.id',
                            'FishBoxStockCycle.fish_box_id',
                            'FishBoxStockCycle.fish_type_id',
                        ]);
                    },
                ])
                ->where('broker_id', $brokerId)
                ->where('box_status', FishBoxStatusConstant::IN_STOCK)
                ->whereNotIn('id', $reservedBoxIds)
                ->whereHas('currentPurchase', function ($query) use ($fishTypeId) {
                    $query->where('fish_type_id', $fishTypeId);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($quantity)
                ->get();

            if ($availableBoxes->count() < $quantity) {
                throw ValidationException::withMessages([
                    'sales_details' => 'Not enough available fish boxes for one of the selected fish. Refresh the transaction and try again.',
                ]);
            }

            $assignedBoxIds = $availableBoxes
                ->pluck('id')
                ->map(fn ($boxId): int => (int) $boxId)
                ->values()
                ->all();

            $salesDetails[$index]['box_id'] = $assignedBoxIds;
            $reservedBoxIds = array_values(array_unique(array_merge($reservedBoxIds, $assignedBoxIds)));
        }

        return $salesDetails;
    }

    /**
     * Lock the selected fish boxes so concurrent sales cannot reuse them mid-transaction.
     */
    private static function lockFishBoxesForUpdate(int $brokerId, array $boxIds): Collection
    {
        if ($boxIds === []) {
            return new Collection();
        }

        $lockedFishBoxes = FishBox::query()
            ->with([
                'currentPurchase' => function ($query) {
                    $query->select([
                        'FishBoxStockCycle.id',
                        'FishBoxStockCycle.fish_box_id',
                        'FishBoxStockCycle.fish_type_id',
                    ]);
                },
            ])
            ->where('broker_id', $brokerId)
            ->whereIn('id', $boxIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($lockedFishBoxes->count() !== count($boxIds)) {
            throw ValidationException::withMessages([
                'sales_details' => 'One or more selected fish boxes are no longer available. Refresh the sales form and try again.',
            ]);
        }

        return $lockedFishBoxes;
    }

    /**
     * Ensure every selected fish box is still sellable and return the locked purchase IDs to attach.
     *
     * @param Collection<int, FishBox> $lockedFishBoxes
     * @param array<int, int> $requestedBoxIds
     * @return array<int, int>
     */
    private static function resolveSellablePurchaseIds(Collection $lockedFishBoxes, array $requestedBoxIds): array
    {
        $purchaseIdsByBoxId = [];

        foreach ($requestedBoxIds as $boxId) {
            $fishBox = $lockedFishBoxes->get($boxId);

            if (
                !$fishBox
                || $fishBox->status !== FishBoxStatusConstant::IN_STOCK
                || !$fishBox->currentPurchase
            ) {
                throw ValidationException::withMessages([
                    'sales_details' => 'One or more selected fish boxes were already used in another transaction. Refresh the sales form and try again.',
                ]);
            }

            $purchaseIdsByBoxId[$boxId] = (int) $fishBox->currentPurchase->id;
        }

        return $purchaseIdsByBoxId;
    }

    /**
     * Add aggregated payment totals without loading each payment row.
     */
    public function scopeWithPaidAmount(Builder $query): Builder
    {
        return $query->withSum('salesPayments as paid_amount_total', 'paid_amount');
    }

    /**
     * Shared payments aggregate for reporting queries.
     */
    public static function paymentTotalsSubquery(?string $paymentDateTo = null): Builder
    {
        $query = PaymentRecord::query()
            ->selectRaw('sale_id, SUM(paid_amount) as paid_total')
            ->groupBy('sale_id');

        if ($paymentDateTo) {
            self::applyDateConstraint($query, 'payment_date', '<=', $paymentDateTo);
        }

        return $query;
    }

    /**
     * Shared sales-detail aggregate for reporting queries.
     */
    public static function salesDetailCountsSubquery(): Builder
    {
        return TransactionLineItem::query()
            ->selectRaw('sale_id, COUNT(*) as fish_box_count')
            ->groupBy('sale_id');
    }

    /**
     * Apply a driver-safe date comparison.
     */
    public static function applyDateConstraint($query, string $column, string $operator, string $value)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query->whereDate($column, $operator, $value);
        }

        return $query->where($column, $operator, $value);
    }

    /**
     * Apply a driver-safe date range.
     */
    public static function applyDateRange($query, string $column, string $dateFrom, string $dateTo)
    {
        self::applyDateConstraint($query, $column, '>=', $dateFrom);
        self::applyDateConstraint($query, $column, '<=', $dateTo);

        return $query;
    }

    /**
     * Apply shared filters while preserving the current sales workflow.
     */
    protected static function applySalesFilters(
        Builder $query,
        ?string $search = null,
        ?string $status = null,
        ?int $brokerId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $createdByUserId = null
    ): Builder {
        $query->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());

        if ($brokerId) {
            $query->where('SalesTransaction.broker_id', $brokerId);
        }

        if ($createdByUserId) {
            $query->where('SalesTransaction.created_by_user_id', $createdByUserId);
        }

        if ($search) {
            $query->whereHas('buyer', function ($buyerQuery) use ($search) {
                $buyerQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) like ?", ["%{$search}%"])
                    ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('SalesTransaction.status', $status);
        }

        if ($dateFrom) {
            self::applyDateConstraint($query, 'SalesTransaction.sales_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            self::applyDateConstraint($query, 'SalesTransaction.sales_date', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Build a report-friendly query that already includes payment totals.
     */
    protected static function buildAggregateSalesQuery(
        ?string $search = null,
        ?string $status = null,
        ?int $brokerId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        bool $includeFishBoxCount = false
    ): Builder {
        $query = self::query()
            ->leftJoinSub(self::paymentTotalsSubquery(), 'payment_totals', function ($join) {
                $join->on('SalesTransaction.id', '=', 'payment_totals.sale_id');
            });

        if ($includeFishBoxCount) {
            $query->leftJoinSub(self::salesDetailCountsSubquery(), 'sales_detail_counts', function ($join) {
                $join->on('SalesTransaction.id', '=', 'sales_detail_counts.sale_id');
            });
        }

        return self::applySalesFilters($query, $search, $status, $brokerId, $dateFrom, $dateTo);
    }

    /**
     * @param string|null $search
     * @param string|null $status
     * @param int|null $brokerId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     *
     * @return LengthAwarePaginator
     */
    public static function getPaginatedWithFilters(?string $search = null, ?string $status = null, ?int $brokerId = null, ?string $dateFrom = null, ?string $dateTo = null) : LengthAwarePaginator
    {
        $query = self::query()
            ->withPaidAmount()
            ->with(['buyer', 'creator.employee', 'creator.roles', 'salesDetails.fishBoxPurchase.fishType']);

        $query = self::applySalesFilters($query, $search, $status, $brokerId, $dateFrom, $dateTo);

        $sales = $query->orderBy('SalesTransaction.created_at', 'desc')->paginate(15);

        // Add formatted items to each sale
        $sales->getCollection()->each(function ($sale) {
            $sale->formatted_items = $sale->getFormattedItems();
        });

        return $sales;
    }

    public static function getReportWithFilters(?string $search = null, ?string $status = null, ?int $brokerId = null, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $query = self::query()
            ->withPaidAmount()
            ->with([
                'buyer',
                'creator.employee',
                'creator.roles',
                'broker.user',
                'salesDetails.fishBoxPurchase.fishType',
                'salesDetails.fishBoxPurchase.fishBox',
            ]);

        $query = self::applySalesFilters($query, $search, $status, $brokerId, $dateFrom, $dateTo);

        $sales = $query->orderBy('SalesTransaction.sales_date', 'desc')
            ->orderBy('SalesTransaction.created_at', 'desc')
            ->get();

        $sales->each(function ($sale) {
            $sale->formatted_items = $sale->getFormattedItems();
        });

        return $sales;
    }

    public static function getPaginatedForCashier(?string $search, ?string $status, int $brokerId, int $cashierId, string $date): LengthAwarePaginator
    {
        $query = self::query()
            ->withPaidAmount()
            ->with(['buyer', 'creator.employee', 'creator.roles', 'salesDetails.fishBoxPurchase.fishType']);

        $query = self::applySalesFilters($query, $search, $status, $brokerId, $date, $date, $cashierId);

        $sales = $query->orderBy('SalesTransaction.created_at', 'desc')->paginate(15);

        $sales->getCollection()->each(function ($sale) {
            $sale->formatted_items = $sale->getFormattedItems();
        });

        return $sales;
    }

    /**
     * Get summary metrics for the filtered sales list.
     */
    public static function getSummaryForFilters(?string $search = null, ?string $status = null, ?int $brokerId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $summary = self::buildAggregateSalesQuery($search, $status, $brokerId, $dateFrom, $dateTo)
            ->toBase()
            ->selectRaw('
                COUNT(SalesTransaction.id) as sales_count,
                COALESCE(SUM(SalesTransaction.total_amount), 0) as gross_total,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as paid_total,
                COALESCE(SUM(CASE
                    WHEN SalesTransaction.total_amount > COALESCE(payment_totals.paid_total, 0)
                    THEN SalesTransaction.total_amount - COALESCE(payment_totals.paid_total, 0)
                    ELSE 0
                END), 0) as balance_total
            ')
            ->first();

        return [
            'count' => (int) ($summary->sales_count ?? 0),
            'gross_total' => (float) ($summary->gross_total ?? 0),
            'paid_total' => (float) ($summary->paid_total ?? 0),
            'balance_total' => (float) ($summary->balance_total ?? 0),
        ];
    }

    public static function getSummaryForCashier(?string $search, ?string $status, int $brokerId, int $cashierId, string $date): array
    {
        $summary = self::buildAggregateSalesQuery($search, $status, $brokerId, $date, $date)
            ->where('SalesTransaction.created_by_user_id', $cashierId)
            ->toBase()
            ->selectRaw('
                COUNT(SalesTransaction.id) as sales_count,
                COALESCE(SUM(SalesTransaction.total_amount), 0) as gross_total,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as paid_total,
                COALESCE(SUM(CASE
                    WHEN SalesTransaction.total_amount > COALESCE(payment_totals.paid_total, 0)
                    THEN SalesTransaction.total_amount - COALESCE(payment_totals.paid_total, 0)
                    ELSE 0
                END), 0) as balance_total
            ')
            ->first();

        return [
            'count' => (int) ($summary->sales_count ?? 0),
            'gross_total' => (float) ($summary->gross_total ?? 0),
            'paid_total' => (float) ($summary->paid_total ?? 0),
            'balance_total' => (float) ($summary->balance_total ?? 0),
        ];
    }

    /**
     * @return void
     */
    public function deleteSales(): void
    {
        // Delete related sales details
        $this->salesDetails()->delete();

        // Mark the sale as deleted
        $this->update(['status' => SalesStatusConstant::DELETED]);
    }

    /**
     * @param int|null $brokerId
     *
     * @return float
     */
    public static function getTotalSalesToday(?int $brokerId): float
    {
        $query = self::whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateConstraint($query, 'sales_date', '=', today()->toDateString());

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        return (float) $query->sum('total_amount');
    }

    /**
     * @param int|null $brokerId
     *
     * @return float
     */
    public static function getTotalPaidAmountToday(?int $brokerId): float
    {
        return (float) (self::buildAggregateSalesQuery(
            null,
            null,
            $brokerId,
            today()->toDateString(),
            today()->toDateString()
        )->toBase()
            ->selectRaw('COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as paid_total')
            ->value('paid_total') ?? 0);
    }

    /**
     * @param int|null $brokerId
     *
     * @return float
     */
    public static function getTotalPaidAmountYesterday(?int $brokerId): float
    {
        $yesterday = Carbon::yesterday()->toDateString();

        return (float) (self::buildAggregateSalesQuery(
            null,
            null,
            $brokerId,
            $yesterday,
            $yesterday
        )->toBase()
            ->selectRaw('COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as paid_total')
            ->value('paid_total') ?? 0);
    }

    /**
     * @param int $limit
     * @param int|null $brokerId
     *
     * @return Collection
     */
    public static function getRecentSales(int $limit = 4, ?int $brokerId = null): Collection
    {
        $query = self::query()
            ->withPaidAmount()
            ->with(['buyer', 'salesDetails.fishBoxPurchase.fishType']);

        $query = self::applySalesFilters($query, null, null, $brokerId);

        $sales = $query->orderBy('SalesTransaction.created_at', 'desc')
            ->limit($limit)
            ->get();

        // Add formatted items to each sale
        $sales->each(function ($sale) {
            $sale->formatted_items = $sale->getFormattedItems();
        });

        return $sales;
    }

    /**
     * @return string
     */
    public function getFormattedItems(): string
    {
        return $this->salesDetails->pluck('item')->filter()->unique()->implode(', ');
    }

    /**
     * Get formatted sale ID in the format #000-000-001
     *
     * @return string
     */
    public function getFormattedIdAttribute(): string
    {
        $id = str_pad($this->id, 9, '0', STR_PAD_LEFT);
        return '#' . substr($id, 0, 3) . '-' . substr($id, 3, 3) . '-' . substr($id, 6, 3);
    }


    /**
     * @param int|null $brokerId
     *
     * @return float
     */
    public static function getTotalSalesBalance(?int $brokerId, ?string $asOfDate = null): float
    {
        $query = self::query()
            ->leftJoinSub(self::paymentTotalsSubquery($asOfDate), 'payment_totals', function ($join) {
                $join->on('SalesTransaction.id', '=', 'payment_totals.sale_id');
            })
            ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());

        if ($brokerId) {
            $query->where('SalesTransaction.broker_id', $brokerId);
        }

        if ($asOfDate) {
            self::applyDateConstraint($query, 'SalesTransaction.sales_date', '<=', $asOfDate);
        }

        return (float) ($query->toBase()
            ->selectRaw('COALESCE(SUM(CASE
                WHEN SalesTransaction.total_amount > COALESCE(payment_totals.paid_total, 0)
                THEN SalesTransaction.total_amount - COALESCE(payment_totals.paid_total, 0)
                ELSE 0
            END), 0) as balance_total')
            ->value('balance_total') ?? 0);
    }

    /**
     * @param int|null $brokerId
     *
     * @return int
     */
    public static function getTotalOrdersToday(?int $brokerId): int
    {
        $query = self::whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateConstraint($query, 'sales_date', '=', today()->toDateString());

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        return $query->count();
    }

    /**
     * Count fish boxes sold today. Each transaction line item represents one sold box.
     */
    public static function getTotalSoldBoxesToday(?int $brokerId): int
    {
        $query = TransactionLineItem::query()
            ->join('SalesTransaction', 'SalesTransaction.id', '=', 'TransactionLineItem.sale_id')
            ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateConstraint($query, 'SalesTransaction.sales_date', '=', today()->toDateString());

        if ($brokerId) {
            $query->where('SalesTransaction.broker_id', $brokerId);
        }

        return $query->count();
    }

    /**
     * Get daily sales data for the last 7 days including today
     *
     * @param int|null $brokerId
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getDailySalesLast7Days(?int $brokerId): \Illuminate\Support\Collection
    {
        $query = self::whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateRange(
            $query,
            'sales_date',
            Carbon::now()->subDays(6)->toDateString(),
            Carbon::now()->toDateString()
        );

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        $dailySales = $query->selectRaw('DATE(sales_date) as date, SUM(total_amount) as total_sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Create array for last 7 days with default values
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->format('D');

            $salesData = $dailySales->where('date', $date)->first();
            $totalSales = $salesData ? (float) $salesData->total_sales : 0;

            $last7Days[] = [
                'date' => $date,
                'day' => $dayName,
                'sales' => $totalSales
            ];
        }

        return collect($last7Days);
    }

    /**
     * Get daily sales data for the current calendar week, Sunday through Saturday.
     *
     * @param int|null $brokerId
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getDailySalesCurrentWeek(?int $brokerId): \Illuminate\Support\Collection
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        return static::getDailySalesForMarketWeek($brokerId, $weekStart, $weekEnd);
    }

    /**
     * Get daily sales data for the previous calendar week, Sunday through Saturday.
     *
     * @param int|null $brokerId
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getDailySalesPreviousMarketWeek(?int $brokerId): \Illuminate\Support\Collection
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->subWeek();
        $weekEnd = $weekStart->copy()->addDays(6);

        return static::getDailySalesForMarketWeek($brokerId, $weekStart, $weekEnd);
    }

    private static function getDailySalesForMarketWeek(?int $brokerId, Carbon $weekStart, Carbon $weekEnd): \Illuminate\Support\Collection
    {
        $query = self::whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateRange(
            $query,
            'sales_date',
            $weekStart->toDateString(),
            $weekEnd->toDateString()
        );

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        $dailySales = $query->selectRaw('DATE(sales_date) as date, SUM(total_amount) as total_sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $weekDays = [];
        for ($date = $weekStart->copy(); $date->lte($weekEnd); $date->addDay()) {
            $dateString = $date->toDateString();
            $salesData = $dailySales->firstWhere('date', $dateString);

            $weekDays[] = [
                'date' => $dateString,
                'day' => $date->format('D'),
                'sales' => $salesData ? (float) $salesData->total_sales : 0,
            ];
        }

        return collect($weekDays);
    }

    /**
     * Get analytics data for a specific date range
     *
     * @param int|null $brokerId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $status
     * @return array
     */
    public static function getAnalyticsData(?int $brokerId, ?string $dateFrom = null, ?string $dateTo = null, ?string $status = null): array
    {
        if (!$dateFrom) {
            $dateFrom = Carbon::now()->subDays(6)->format('Y-m-d');
        }
        if (!$dateTo) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $summary = self::buildAggregateSalesQuery(
            null,
            $status,
            $brokerId,
            $dateFrom,
            $dateTo,
            true
        )->toBase()
            ->selectRaw('
            COUNT(SalesTransaction.id) as total_orders,
            COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_revenue,
            COALESCE(SUM(CASE
                WHEN SalesTransaction.total_amount > COALESCE(payment_totals.paid_total, 0)
                THEN SalesTransaction.total_amount - COALESCE(payment_totals.paid_total, 0)
                ELSE 0
            END), 0) as total_balance,
            COALESCE(SUM(COALESCE(sales_detail_counts.fish_box_count, 0)), 0) as total_fish_boxes
        ')->first();
        $weeklySalesData = self::getDailySalesForPeriod($brokerId, $dateFrom, $dateTo, $status);
        $topItems = self::getTopSellingItems($brokerId, $dateFrom, $dateTo, 5, $status);
        $paymentMethods = PaymentRecord::getPaymentMethodsBreakdown($brokerId, $dateFrom, $dateTo, $status);

        return [
            'totalRevenue' => (float) ($summary->total_revenue ?? 0),
            'totalOrders' => (int) ($summary->total_orders ?? 0),
            'totalBalance' => (float) ($summary->total_balance ?? 0),
            'totalFishBoxes' => (int) ($summary->total_fish_boxes ?? 0),
            'weeklySalesData' => $weeklySalesData,
            'topItems' => $topItems,
            'paymentMethods' => $paymentMethods,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ];
    }

    public static function getDailySalesForPeriod(?int $brokerId, string $dateFrom, string $dateTo, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = self::whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $dailySales = $query->selectRaw('DATE(sales_date) as date, SUM(total_amount) as total_sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $periodDays = [];
        $currentDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->startOfDay();

        while ($currentDate->lte($endDate)) {
            $date = $currentDate->format('Y-m-d');
            $salesData = $dailySales->firstWhere('date', $date);

            $periodDays[] = [
                'date' => $date,
                'day' => $currentDate->format('M. j'),
                'sales' => $salesData ? (float) $salesData->total_sales : 0,
            ];

            $currentDate->addDay();
        }

        return collect($periodDays);
    }

    /**
     * Get weekly sales data for a specific period.
     */
    public static function getWeeklySalesForPeriod(?int $brokerId, string $dateFrom, string $dateTo, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = self::whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);

        if ($brokerId) {
            $query->where('broker_id', $brokerId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $weekExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%W', sales_date)"
            : 'YEARWEEK(sales_date, 1)';

        $weeklySales = $query->selectRaw("{$weekExpression} as week, MIN(sales_date) as week_start, MAX(sales_date) as week_end, SUM(total_amount) as total_sales")
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Create array for the period with weekly data
        $periodWeeks = [];
        $startDate = Carbon::parse($dateFrom)->startOfWeek();
        $endDate = Carbon::parse($dateTo)->endOfWeek();

        while ($startDate->lte($endDate)) {
            $weekStart = $startDate->format('Y-m-d');
            $weekEnd = $startDate->copy()->endOfWeek()->format('Y-m-d');

            // Check if start and end of week are in different months
            $startMonth = $startDate->format('M');
            $endMonth = $startDate->copy()->endOfWeek()->format('M');

            if ($startMonth === $endMonth) {
                // Same month: "Sep. 1-7"
                $weekLabel = $startDate->format('M. j') . '-' . $startDate->copy()->endOfWeek()->format('j');
            } else {
                // Different months: "Jul. 28- Aug. 3"
                $weekLabel = $startDate->format('M. j') . '- ' . $startDate->copy()->endOfWeek()->format('M. j');
            }

            // Find matching sales data by checking if the sales date falls within this week
            $salesData = $weeklySales->filter(function ($sale) use ($weekStart, $weekEnd) {
                $saleStart = Carbon::parse($sale->week_start)->format('Y-m-d');
                $saleEnd = Carbon::parse($sale->week_end)->format('Y-m-d');
                return $saleStart >= $weekStart && $saleEnd <= $weekEnd;
            })->first();

            $totalSales = $salesData ? (float) $salesData->total_sales : 0;

            $periodWeeks[] = [
                'date' => $weekStart,
                'day' => $weekLabel,
                'sales' => $totalSales,
                'week_start' => $weekStart,
                'week_end' => $weekEnd
            ];

            $startDate->addWeek();
        }

        return collect($periodWeeks);
    }

    /**
     * Get top selling items for a period
     *
     * @param int|null $brokerId
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $limit
     * @param string|null $status
     * @return \Illuminate\Support\Collection
     */
    public static function getTopSellingItems(?int $brokerId, string $dateFrom, string $dateTo, int $limit = 5, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = TransactionLineItem::query()
            ->join('SalesTransaction', 'SalesTransaction.id', '=', 'TransactionLineItem.sale_id')
            ->leftJoin('FishBoxStockCycle', 'FishBoxStockCycle.id', '=', 'TransactionLineItem.fish_box_purchase_id')
            ->leftJoin('FishType', 'FishType.id', '=', 'FishBoxStockCycle.fish_type_id')
            ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateRange($query, 'SalesTransaction.sales_date', $dateFrom, $dateTo);

        if ($brokerId) {
            $query->where('SalesTransaction.broker_id', $brokerId);
        }

        if ($status) {
            $query->where('SalesTransaction.status', $status);
        }

        return $query
            ->selectRaw("
                COALESCE(FishType.name, '') as name,
                COUNT(TransactionLineItem.id) as item_quantity,
                COALESCE(SUM(TransactionLineItem.sub_total), 0) as item_revenue
            ")
            ->groupBy(DB::raw("COALESCE(FishType.name, '')"))
            ->orderByDesc('item_quantity')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => (int) $item->item_quantity,
                    'revenue' => (float) $item->item_revenue,
                ];
            })
            ->values();
    }

}
