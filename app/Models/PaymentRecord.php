<?php

namespace App\Models;

use App\Constants\SalesStatusConstant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRecord extends Model
{
    use HasFactory;

    protected $table = 'PaymentRecord';

    protected $fillable = [
        'sale_id',
        'paid_amount',
        'payment_date',
        'payment_method',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * @return BelongsTo
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sale_id');
    }

    /**
     * Alias for singular naming.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sale_id');
    }

    /**
     * Compatibility accessor for older payment tables.
     */
    public function getStatusAttribute(): string
    {
        return 'Active';
    }

    /**
     * Expose the broker through the related sale.
     */
    public function getBrokerAttribute(): ?Broker
    {
        return $this->sales?->broker;
    }

    /**
     * Compatibility accessor for authorization checks that still read broker_id.
     */
    public function getBrokerIdAttribute(): ?int
    {
        return $this->sales?->broker_id;
    }

    /**
     * Get payment methods breakdown for a period
     *
     * @param int|null $brokerId
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @return \Illuminate\Support\Collection
     */
    public static function getPaymentMethodsBreakdown(?int $brokerId, string $dateFrom, string $dateTo, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = static::query()
            ->join('SalesTransaction', 'SalesTransaction.id', '=', 'PaymentRecord.sale_id')
            ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());

        SalesTransaction::applyDateRange($query, 'SalesTransaction.sales_date', $dateFrom, $dateTo);

        if ($brokerId) {
            $query->where('SalesTransaction.broker_id', $brokerId);
        }

        if ($status) {
            $query->where('SalesTransaction.status', $status);
        }

        $payments = $query->selectRaw('PaymentRecord.payment_method, COUNT(*) as transactions, SUM(PaymentRecord.paid_amount) as amount')
            ->groupBy('PaymentRecord.payment_method')
            ->get();

        $totalAmount = $payments->sum('amount');

        return $payments->map(function ($payment) use ($totalAmount) {
            return [
                'name' => $payment->payment_method,
                'transactions' => $payment->transactions,
                'amount' => $payment->amount,
                'percentage' => $totalAmount > 0 ? round(($payment->amount / $totalAmount) * 100, 1) : 0
            ];
        });
    }

    /**
     * @param int|null $brokerId
     * @return float
     */
    public static function getTotalSalesToday(?int $brokerId): float
    {
        $query = self::query();

        SalesTransaction::applyDateConstraint($query, 'payment_date', '=', today()->toDateString());

        if ($brokerId) {
            $query->join('SalesTransaction', 'SalesTransaction.id', '=', 'PaymentRecord.sale_id')
                ->where('SalesTransaction.broker_id', $brokerId);
        }

        return (float) $query->sum('PaymentRecord.paid_amount');
    }
}
