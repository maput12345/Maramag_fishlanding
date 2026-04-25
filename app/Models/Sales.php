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
use App\Constants\FishBoxStatusConstant;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_date',
        'broker_id',
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

    /**
     * @return HasMany
     */
    public function salesDetails() : HasMany
    {
        return $this->hasMany(SalesDetails::class, 'sale_id');
    }

    /**
     * @return HasMany
     */
    public function salesPayments() : HasMany
    {
        return $this->hasMany(SalesPayment::class, 'sale_id');
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
     * @return Sales
     */
    public static function createSalesWithDetails(array $salesData, array $salesDetails, int $brokerId, ?array $initialPayment = null): Sales
    {
        return DB::transaction(function () use ($salesData, $salesDetails, $brokerId, $initialPayment) {
            $userId = Auth::id();
            $buyer = Buyer::resolveForSale($salesData['buyer_name'], $salesData['buyer_contact'] ?? null);

            $sale = self::create([
                'sales_date' => $salesData['sales_date'],
                'broker_id' => $brokerId,
                'buyer_id' => $buyer->id,
                'total_amount' => $salesData['total_amount'],
                'status' => SalesStatusConstant::ACTIVE
            ]);

            SalesDetails::createSalesDetails($sale->id, $brokerId, $salesDetails);
            FishBox::updateFishBoxesForSales($brokerId, $salesDetails, $userId);

            if (!empty($initialPayment['paid_amount'])) {
                SalesPayment::create([
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
     * @param Sales $sale
     * @param array $salesData
     * @param array $salesDetails
     * @param int $brokerId
     * @return void
     */
    public static function updateSalesWithDetails(Sales $sale, array $salesData, array $salesDetails, int $brokerId): void
    {
        DB::transaction(function () use ($sale, $salesData, $salesDetails, $brokerId) {
            $userId = Auth::id();
            $buyer = Buyer::resolveForSale($salesData['buyer_name'], $salesData['buyer_contact'] ?? null);

            $sale->update([
                'sales_date' => $salesData['sales_date'],
                'buyer_id' => $buyer->id,
                'total_amount' => $salesData['total_amount'],
            ]);

            foreach ($sale->salesDetails as $detail) {
                $fishBoxId = $detail->fishBoxPurchase?->fish_box_id;

                if ($fishBoxId) {
                    FishBox::updateStatus($fishBoxId, FishBoxStatusConstant::IN_STOCK, $userId);
                    InventoryLog::deleteLogForFishBox($fishBoxId, $sale->created_at);
                }
            }

            $sale->salesDetails()->delete();
            SalesDetails::createSalesDetails($sale->id, $brokerId, $salesDetails);
            FishBox::updateFishBoxesForSales($brokerId, $salesDetails, $userId);
            $sale->refresh();
            $sale->updatePaymentStatus();
        });
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
    public static function paymentTotalsSubquery(): Builder
    {
        return SalesPayment::query()
            ->selectRaw('sale_id, SUM(paid_amount) as paid_total')
            ->groupBy('sale_id');
    }

    /**
     * Shared sales-detail aggregate for reporting queries.
     */
    public static function salesDetailCountsSubquery(): Builder
    {
        return SalesDetails::query()
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
        ?string $dateTo = null
    ): Builder {
        $query->whereIn('sales.status', SalesStatusConstant::getAllActiveStatuses());

        if ($brokerId) {
            $query->where('sales.broker_id', $brokerId);
        }

        if ($search) {
            $query->whereHas('buyer', function ($buyerQuery) use ($search) {
                $buyerQuery->whereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) like ?", ["%{$search}%"])
                    ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('sales.status', $status);
        }

        if ($dateFrom) {
            self::applyDateConstraint($query, 'sales.sales_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            self::applyDateConstraint($query, 'sales.sales_date', '<=', $dateTo);
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
                $join->on('sales.id', '=', 'payment_totals.sale_id');
            });

        if ($includeFishBoxCount) {
            $query->leftJoinSub(self::salesDetailCountsSubquery(), 'sales_detail_counts', function ($join) {
                $join->on('sales.id', '=', 'sales_detail_counts.sale_id');
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
    public static function getPaginatedWithFilters(?string $search = null, ?string $status = null, ?int $brokerId, ?string $dateFrom = null, ?string $dateTo = null) : LengthAwarePaginator
    {
        $query = self::query()
            ->withPaidAmount()
            ->with(['buyer', 'salesDetails.fishBoxPurchase.fishType']);

        $query = self::applySalesFilters($query, $search, $status, $brokerId, $dateFrom, $dateTo);

        $sales = $query->orderBy('sales.created_at', 'desc')->paginate(15);

        // Add formatted items to each sale
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
                COUNT(sales.id) as sales_count,
                COALESCE(SUM(sales.total_amount), 0) as gross_total,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as paid_total,
                COALESCE(SUM(CASE
                    WHEN sales.total_amount > COALESCE(payment_totals.paid_total, 0)
                    THEN sales.total_amount - COALESCE(payment_totals.paid_total, 0)
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
    public static function getRecentSales($limit = 4, ?int $brokerId): Collection
    {
        $query = self::query()
            ->withPaidAmount()
            ->with(['buyer', 'salesDetails.fishBoxPurchase.fishType']);

        $query = self::applySalesFilters($query, null, null, $brokerId);

        $sales = $query->orderBy('sales.created_at', 'desc')
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
    public static function getTotalSalesBalance(?int $brokerId): float
    {
        return (float) (self::buildAggregateSalesQuery(
            null,
            null,
            $brokerId
        )->toBase()
            ->selectRaw('COALESCE(SUM(CASE
                WHEN sales.total_amount > COALESCE(payment_totals.paid_total, 0)
                THEN sales.total_amount - COALESCE(payment_totals.paid_total, 0)
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
            COUNT(sales.id) as total_orders,
            COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_revenue,
            COALESCE(SUM(CASE
                WHEN sales.total_amount > COALESCE(payment_totals.paid_total, 0)
                THEN sales.total_amount - COALESCE(payment_totals.paid_total, 0)
                ELSE 0
            END), 0) as total_balance,
            COALESCE(SUM(COALESCE(sales_detail_counts.fish_box_count, 0)), 0) as total_fish_boxes
        ')->first();
        $weeklySalesData = self::getDailySalesForPeriod($brokerId, $dateFrom, $dateTo, $status);
        $topItems = self::getTopSellingItems($brokerId, $dateFrom, $dateTo, 5, $status);
        $paymentMethods = SalesPayment::getPaymentMethodsBreakdown($brokerId, $dateFrom, $dateTo, $status);

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

    /**
     * Get daily sales data for a specific period
     *
     * @param int|null $brokerId
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @return \Illuminate\Support\Collection
     */
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
        $query = SalesDetails::query()
            ->join('sales', 'sales.id', '=', 'sales_details.sale_id')
            ->leftJoin('fish_box_purchases', 'fish_box_purchases.id', '=', 'sales_details.fish_box_purchase_id')
            ->leftJoin('fish_types', 'fish_types.id', '=', 'fish_box_purchases.fish_type_id')
            ->whereIn('sales.status', SalesStatusConstant::getAllActiveStatuses());

        self::applyDateRange($query, 'sales.sales_date', $dateFrom, $dateTo);

        if ($brokerId) {
            $query->where('sales.broker_id', $brokerId);
        }

        if ($status) {
            $query->where('sales.status', $status);
        }

        return $query
            ->selectRaw("
                COALESCE(fish_types.name, '') as name,
                COUNT(sales_details.id) as item_quantity,
                COALESCE(SUM(sales_details.sub_total), 0) as item_revenue
            ")
            ->groupBy(DB::raw("COALESCE(fish_types.name, '')"))
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
