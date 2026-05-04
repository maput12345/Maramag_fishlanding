<?php

namespace App\Models;

use App\Constants\SalesStatusConstant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class FinancialStatementEntry extends Model
{
    use HasFactory;

    protected $table = 'FinancialStatementEntry';

    public const TYPE_SGA = 'selling_general_and_administrative';
    public const TYPE_LOSS_ON_SALE = 'loss_on_sale';

    protected $fillable = [
        'broker_id',
        'created_by_user_id',
        'statement_date',
        'entry_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['entry_type_label'];

    /**
     * Get the broker that owns this daily adjustment.
     */
    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class, 'broker_id');
    }

    /**
     * Get the user who recorded this daily adjustment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope entries to one broker.
     */
    public function scopeForBroker(Builder $query, int $brokerId): Builder
    {
        return $query->where('broker_id', $brokerId);
    }

    /**
     * Scope entries to one statement date.
     */
    public function scopeForStatementDate(Builder $query, Carbon|string $statementDate): Builder
    {
        $date = $statementDate instanceof Carbon
            ? $statementDate->toDateString()
            : $statementDate;

        return SalesTransaction::applyDateConstraint($query, 'statement_date', '=', $date);
    }

    /**
     * Scope entries by type.
     */
    public function scopeOfType(Builder $query, string $entryType): Builder
    {
        return $query->where('entry_type', $entryType);
    }

    /**
     * Get the supported manual adjustment types.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_SGA => 'Selling, General and Administrative Expenses',
            self::TYPE_LOSS_ON_SALE => 'Loss on Sale',
        ];
    }

    /**
     * Get a readable label for the stored adjustment type.
     */
    public static function getTypeLabel(string $entryType): string
    {
        return static::typeOptions()[$entryType] ?? $entryType;
    }

    /**
     * Expose the readable type label to the UI.
     */
    public function getEntryTypeLabelAttribute(): string
    {
        return static::getTypeLabel($this->entry_type);
    }

    /**
     * Get the manual daily totals for each statement line that still needs user input.
     *
     * @return array<string, float>
     */
    public static function getDailyEntryTotals(int $brokerId, Carbon|string $statementDate): array
    {
        $totals = static::query()
            ->forBroker($brokerId)
            ->forStatementDate($statementDate)
            ->selectRaw('entry_type, COALESCE(SUM(amount), 0) as total')
            ->groupBy('entry_type')
            ->pluck('total', 'entry_type');

        return [
            self::TYPE_SGA => (float) ($totals[self::TYPE_SGA] ?? 0),
            self::TYPE_LOSS_ON_SALE => (float) ($totals[self::TYPE_LOSS_ON_SALE] ?? 0),
        ];
    }

    /**
     * Build the daily financial statement using sales data plus manual adjustments.
     *
     * @return array<string, float|int|string>
     */
    public static function getDailyStatement(int $brokerId, Carbon|string $statementDate): array
    {
        $date = $statementDate instanceof Carbon
            ? $statementDate->toDateString()
            : $statementDate;

        $activeStatuses = SalesStatusConstant::getAllActiveStatuses();

        $salesBaseQuery = SalesTransaction::query()
            ->where('broker_id', $brokerId)
            ->whereIn('status', $activeStatuses);

        SalesTransaction::applyDateConstraint($salesBaseQuery, 'sales_date', '=', $date);

        $costBaseQuery = TransactionLineItem::query()
            ->join('SalesTransaction', 'SalesTransaction.id', '=', 'TransactionLineItem.sale_id')
            ->join('FishBoxStockCycle', 'FishBoxStockCycle.id', '=', 'TransactionLineItem.fish_box_purchase_id')
            ->where('SalesTransaction.broker_id', $brokerId)
            ->whereIn('SalesTransaction.status', $activeStatuses);

        SalesTransaction::applyDateConstraint($costBaseQuery, 'SalesTransaction.sales_date', '=', $date);

        $collectionsBaseQuery = PaymentRecord::query()
            ->join('SalesTransaction', 'SalesTransaction.id', '=', 'PaymentRecord.sale_id')
            ->where('SalesTransaction.broker_id', $brokerId)
            ->whereIn('SalesTransaction.status', $activeStatuses);

        SalesTransaction::applyDateConstraint($collectionsBaseQuery, 'PaymentRecord.payment_date', '=', $date);

        $sales = (float) (clone $salesBaseQuery)->sum('total_amount');
        $salesCount = (int) (clone $salesBaseQuery)->count();
        $costOfSales = (float) (clone $costBaseQuery)->sum('FishBoxStockCycle.cost_price');
        $soldBoxes = (int) (clone $costBaseQuery)->count('TransactionLineItem.id');
        $collections = (float) (clone $collectionsBaseQuery)->sum('PaymentRecord.paid_amount');
        $outstandingReceivableBalance = SalesTransaction::getTotalSalesBalance($brokerId, $date);

        $entryTotals = static::getDailyEntryTotals($brokerId, $date);
        $sellingGeneralAndAdministrativeExpenses = $entryTotals[self::TYPE_SGA];
        $lossOnSale = $entryTotals[self::TYPE_LOSS_ON_SALE];

        $grossProfit = $sales - $costOfSales;
        $operatingIncome = $grossProfit - $sellingGeneralAndAdministrativeExpenses;
        $netIncome = $operatingIncome - $lossOnSale;

        return [
            'statement_date' => $date,
            'sales' => $sales,
            'sales_count' => $salesCount,
            'cost_of_sales' => $costOfSales,
            'sold_boxes' => $soldBoxes,
            'gross_profit' => $grossProfit,
            'selling_general_and_administrative_expenses' => $sellingGeneralAndAdministrativeExpenses,
            'operating_income' => $operatingIncome,
            'loss_on_sale' => $lossOnSale,
            'net_income' => $netIncome,
            'collections' => $collections,
            'outstanding_receivable_balance' => $outstandingReceivableBalance,
        ];
    }

    /**
     * Get the supporting sales rows behind the daily financial statement.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function getDailySalesBreakdown(int $brokerId, Carbon|string $statementDate): Collection
    {
        $date = $statementDate instanceof Carbon
            ? $statementDate->toDateString()
            : $statementDate;

        $salesQuery = SalesTransaction::query()
            ->withPaidAmount()
            ->with([
                'buyer:id,first_name,middle_name,last_name,contact',
                'salesDetails:id,sale_id,fish_box_purchase_id,unit_price,sub_total,discount',
                'salesDetails.fishBoxPurchase:id,fish_box_id,fish_type_id,cost_price',
                'salesDetails.fishBoxPurchase.fishType:id,name',
            ])
            ->where('broker_id', $brokerId)
            ->whereIn('status', SalesStatusConstant::getAllActiveStatuses());

        SalesTransaction::applyDateConstraint($salesQuery, 'sales_date', '=', $date);

        return $salesQuery
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (SalesTransaction $sale): array {
                $costOfSales = round(
                    $sale->salesDetails->sum(fn (TransactionLineItem $detail): float => (float) ($detail->fishBoxPurchase?->cost_price ?? 0)),
                    2
                );

                return [
                    'id' => (int) $sale->id,
                    'formatted_id' => $sale->formatted_id,
                    'sales_date' => optional($sale->sales_date)->format('M d, Y'),
                    'buyer_name' => $sale->buyer_name,
                    'commodities' => $sale->salesDetails->pluck('item')->filter()->unique()->implode(', '),
                    'sold_boxes' => $sale->salesDetails->count(),
                    'sales' => (float) $sale->total_amount,
                    'cost_of_sales' => $costOfSales,
                    'gross_profit' => round((float) $sale->total_amount - $costOfSales, 2),
                    'paid_amount' => (float) $sale->paid_amount,
                    'status' => $sale->status,
                ];
            })
            ->values();
    }
}
