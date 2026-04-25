<?php

namespace App\Repositories;

use App\Constants\FishBoxStatusConstant;
use App\Models\Sales;
use App\Models\Broker;
use App\Constants\SalesStatusConstant;
use App\Models\FishBox;
use App\Models\SalesDetails;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesRepository
{
    /**
     * Build a driver-safe broker full-name SQL expression.
     */
    private function brokerNameExpression(string $table = 'brokers'): string
    {
        if (Sales::query()->getConnection()->getDriverName() === 'sqlite') {
            return "TRIM(COALESCE({$table}.first_name, '') || ' ' || COALESCE({$table}.middle_name, '') || ' ' || COALESCE({$table}.last_name, ''))";
        }

        return "TRIM(CONCAT_WS(' ', {$table}.first_name, {$table}.middle_name, {$table}.last_name))";
    }

    /**
     * Get top brokers for admin dashboard with filters
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @param int $limit
     * @return Collection
     */
    public function getTopBrokersForAdmin(string $dateFrom, string $dateTo, ?string $status = null, int $limit = 5): Collection
    {
        $rows = Sales::query()
            ->leftJoinSub(Sales::paymentTotalsSubquery(), 'payment_totals', function ($join) {
                $join->on('sales.id', '=', 'payment_totals.sale_id');
            })
            ->active();

        Sales::applyDateRange($rows, 'sales.sales_date', $dateFrom, $dateTo);

        if ($status) {
            $rows->where('sales.status', $status);
        }

        $rows = $rows
            ->selectRaw('
                sales.broker_id,
                COUNT(sales.id) as sales_count,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_sales
            ')
            ->groupBy('sales.broker_id')
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get();

        $brokers = Broker::with('user')
            ->whereIn('id', $rows->pluck('broker_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($brokers) {
            return [
                'broker' => $brokers->get((int) $row->broker_id),
                'sales_count' => (int) $row->sales_count,
                'total_sales' => (float) $row->total_sales,
            ];
        });
    }

    /**
     * Get daily sales data for admin dashboard with filters
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @return array
     */
    public function getDailySalesDataForAdmin(string $dateFrom, string $dateTo, ?string $status = null): array
    {
        $dailySales = [];
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $daysDiff = $startDate->diffInDays($endDate);

        // Limit to 7 days for chart display
        $chartDays = min($daysDiff + 1, 7);
        $chartStartDate = $endDate->copy()->subDays($chartDays - 1);

        $totalsByDate = Sales::query()
            ->leftJoinSub(Sales::paymentTotalsSubquery(), 'payment_totals', function ($join) {
                $join->on('sales.id', '=', 'payment_totals.sale_id');
            })
            ->active();

        Sales::applyDateRange($totalsByDate, 'sales.sales_date', $chartStartDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        if ($status) {
            $totalsByDate->where('sales.status', $status);
        }

        $totalsByDate = $totalsByDate
            ->selectRaw('sales.sales_date, COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_sales')
            ->groupBy('sales.sales_date')
            ->pluck('total_sales', 'sales.sales_date');

        for ($i = 0; $i < $chartDays; $i++) {
            $date = $chartStartDate->copy()->addDays($i);
            $dayName = $date->format('D');

            $dailySales[] = [
                'label' => $dayName,
                'value' => (float) ($totalsByDate[$date->format('Y-m-d')] ?? 0)
            ];
        }

        return $dailySales;
    }

    /**
     * Get total revenue for admin dashboard
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @return float
     */
    public function getTotalRevenueForAdmin(string $dateFrom, string $dateTo, ?string $status = null): float
    {
        $query = Sales::active();

        Sales::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->sum('total_amount');
    }

    /**
     * Get total orders count for admin dashboard
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @return int
     */
    public function getTotalOrdersForAdmin(string $dateFrom, string $dateTo, ?string $status = null): int
    {
        $query = Sales::active();

        Sales::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    /**
     * Get recent orders for admin dashboard
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     * @param int $limit
     * @return Collection
     */
    public function getRecentOrdersForAdmin(string $dateFrom, string $dateTo, ?string $status = null, int $limit = 5): Collection
    {
        $query = Sales::query()
            ->withPaidAmount()
            ->with(['broker.user', 'salesDetails'])
            ->active();

        Sales::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Get sales status breakdown data
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $status
     *
     * @return array
     */
    public function getSalesStatusBreakdown(string $dateFrom, string $dateTo, ?string $status): array
    {
        $statusBreakdown = [];
        $salesStatuses = SalesStatusConstant::getAllActiveStatuses();

        $rows = Sales::query()
            ->leftJoinSub(Sales::paymentTotalsSubquery(), 'payment_totals', function ($join) {
                $join->on('sales.id', '=', 'payment_totals.sale_id');
            })
            ->active();

        Sales::applyDateRange($rows, 'sales.sales_date', $dateFrom, $dateTo);

        if ($status) {
            $rows->where('sales.status', $status);
        }

        $rows = $rows
            ->selectRaw('
                sales.status,
                COUNT(sales.id) as sales_count,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_sales
            ')
            ->groupBy('sales.status')
            ->get()
            ->keyBy('status');

        foreach ($salesStatuses as $statusValue) {
            $row = $rows->get($statusValue);
            $count = (int) ($row->sales_count ?? 0);
            $totalAmount = (float) ($row->total_sales ?? 0);

            $statusBreakdown[$statusValue] = [
                'count' => $count,
                'total_amount' => $totalAmount,
                'display_name' => SalesStatusConstant::getDisplayName($statusValue),
                'color_class' => SalesStatusConstant::getStatusColorClasses($statusValue),
                'bg_class' => $this->getStatusBackgroundClass($statusValue),
                'progress_color' => $this->getStatusProgressColor($statusValue)
            ];
        }

        $totalStatusOrders = array_sum(array_column($statusBreakdown, 'count'));

        // Calculate percentages
        foreach ($statusBreakdown as $statusValue => &$data) {
            $data['percentage'] = $totalStatusOrders > 0 ? ($data['count'] / $totalStatusOrders) * 100 : 0;
        }

        return [
            'breakdown' => $statusBreakdown,
            'total_orders' => $totalStatusOrders
        ];
    }

    /**
     * Get payment conversion analysis data
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function getPaymentConversionData(string $dateFrom, string $dateTo): array
    {
        $activeOrders = Sales::active()->whereBetween('sales_date', [$dateFrom, $dateTo])->where('status', SalesStatusConstant::ACTIVE)->count();
        $paidOrders = Sales::active()->whereBetween('sales_date', [$dateFrom, $dateTo])->where('status', SalesStatusConstant::PAID)->count();
        $partiallyPaidOrders = Sales::active()->whereBetween('sales_date', [$dateFrom, $dateTo])->where('status', SalesStatusConstant::PARTIALLY_PAID)->count();
        $totalOrders = $activeOrders + $paidOrders + $partiallyPaidOrders;

        return [
            'active_orders' => $activeOrders,
            'paid_orders' => $paidOrders,
            'partially_paid_orders' => $partiallyPaidOrders,
            'total_orders' => $totalOrders,
            'conversion_rate' => $totalOrders > 0 ? ($paidOrders / $totalOrders) * 100 : 0,
            'partial_conversion_rate' => $totalOrders > 0 ? ($partiallyPaidOrders / $totalOrders) * 100 : 0,
        ];
    }

    /**
     * Get background color class for status
     */
    private function getStatusBackgroundClass(string $status): string
    {
        return match ($status) {
            SalesStatusConstant::PAID => 'bg-green-50',
            SalesStatusConstant::ACTIVE => 'bg-yellow-50',
            SalesStatusConstant::PARTIALLY_PAID => 'bg-blue-50',
            default => 'bg-gray-50'
        };
    }

    /**
     * Get progress bar color class for status
     */
    private function getStatusProgressColor(string $status): string
    {
        return match ($status) {
            SalesStatusConstant::PAID => 'bg-green-500',
            SalesStatusConstant::ACTIVE => 'bg-yellow-500',
            SalesStatusConstant::PARTIALLY_PAID => 'bg-blue-500',
            default => 'bg-gray-500'
        };
    }

    /**
     * Get top brokers this month with fishbox count
     *
     * @return Collection
     */
    public function getTopBrokersWithFishBoxCount(): Collection
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $rows = Sales::query()
            ->leftJoinSub(Sales::paymentTotalsSubquery(), 'payment_totals', function ($join) {
                $join->on('sales.id', '=', 'payment_totals.sale_id');
            })
            ->leftJoinSub(Sales::salesDetailCountsSubquery(), 'sales_detail_counts', function ($join) {
                $join->on('sales.id', '=', 'sales_detail_counts.sale_id');
            })
            ->active();

        Sales::applyDateRange($rows, 'sales.sales_date', $startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d'));

        $rows = $rows->selectRaw('
                sales.broker_id,
                COUNT(sales.id) as sales_count,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_sales,
                COALESCE(SUM(COALESCE(sales_detail_counts.fish_box_count, 0)), 0) as fishbox_count
            ')
            ->groupBy('sales.broker_id')
            ->orderByDesc('sales_count')
            ->limit(5)
            ->get();

        $brokers = Broker::with('user')
            ->whereIn('id', $rows->pluck('broker_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function ($brokerData) use ($brokers) {
            return [
                'broker' => $brokers->get((int) $brokerData->broker_id),
                'sales_count' => (int) $brokerData->sales_count,
                'total_sales' => (float) $brokerData->total_sales,
                'fishbox_count' => (int) $brokerData->fishbox_count
            ];
        });
    }

    /**
     * Get all brokers with their sales details for admin analysis
     * Shows only fish type, fish boxes, and date
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $brokerSearch
     * @return LengthAwarePaginator
     */
    public function getBrokersWithSalesDetails(string $dateFrom, string $dateTo, ?string $brokerSearch = null): LengthAwarePaginator
    {
        // Build query with eager loading and constraints
        $brokersQuery = Broker::query()
        ->select(['id', 'user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'stall_name'])
        ->with([
            'user:id,email',
            'sales' => function ($query) use ($dateFrom, $dateTo) {
                $query->select(['id', 'broker_id', 'buyer_id', 'sales_date', 'status', 'created_at'])
                    ->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
                    ->with([
                        'buyer:id,first_name,middle_name,last_name,contact',
                        'salesDetails:id,sale_id,fish_box_purchase_id,unit_price,sub_total,discount',
                        'salesDetails.fishBoxPurchase:id,fish_box_id,fish_type_id',
                        'salesDetails.fishBoxPurchase.fishType:id,name,description',
                        'salesDetails.fishBoxPurchase.fishBox' => function ($fishBoxQuery) {
                            $fishBoxQuery->select(['fish_boxes.id', 'fish_boxes.broker_id', 'fish_boxes.qr_code', 'fish_boxes.box_status'])
                                ->withBrokerBoxNumber();
                        },
                    ])
                    ->orderBy('sales_date', 'desc');

                Sales::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);
            }
        ])
        // Only get brokers who have sales in the date range
        ->whereHas('sales', function ($query) use ($dateFrom, $dateTo) {
            $query->whereIn('status', SalesStatusConstant::getAllActiveStatuses());
            Sales::applyDateRange($query, 'sales_date', $dateFrom, $dateTo);
        });

        // Apply broker search filter if provided
        if ($brokerSearch) {
            $brokerNameExpression = $this->brokerNameExpression();

            $brokersQuery->where(function ($query) use ($brokerSearch, $brokerNameExpression) {
                $query->where('stall_name', 'like', "%{$brokerSearch}%")
                    ->orWhereRaw("{$brokerNameExpression} like ?", ["%{$brokerSearch}%"]);
            });
        }

        $brokersQuery->orderBy('stall_name', 'asc');

        $brokers = $brokersQuery->paginate(10);
        $brokerIds = $brokers->getCollection()->pluck('id')->all();
        $missingFishBoxesByBroker = new Collection();
        $receiptCutoff = rescue(
            fn () => Carbon::parse($dateTo)->endOfDay(),
            Carbon::now()->endOfDay(),
            report: false
        );

        if ($brokerIds !== []) {
            $missingFishBoxesByBroker = FishBox::query()
                ->select(['fish_boxes.id', 'fish_boxes.broker_id', 'fish_boxes.qr_code', 'fish_boxes.box_status'])
                ->withBrokerBoxNumber()
                ->with([
                    'inventoryLogs' => function ($logQuery) use ($receiptCutoff) {
                        $logQuery
                            ->where('fish_inventory.created_at', '<=', $receiptCutoff)
                            ->orderBy('fish_inventory.created_at', 'desc')
                            ->orderBy('fish_inventory.id', 'desc');
                    },
                ])
                ->whereIn('fish_boxes.broker_id', $brokerIds)
                ->whereHas('inventoryLogs', function ($logQuery) use ($receiptCutoff) {
                    $logQuery
                        ->where('fish_inventory.created_at', '<=', $receiptCutoff);
                })
                ->orderBy('fish_boxes.id')
                ->get()
                ->filter(function (FishBox $fishBox) {
                    return $fishBox->inventoryLogs->first()?->status === FishBoxStatusConstant::MISSING;
                })
                ->groupBy('broker_id');
        }

        $brokers->getCollection()->transform(function (Broker $broker) use ($missingFishBoxesByBroker) {
            $broker->setRelation('missingFishBoxesForReceipt', $missingFishBoxesByBroker->get($broker->id, collect()));

            return $broker;
        });

        return $brokers;
    }

    /**
     * Get total fishboxes sold based on filters
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $brokerSearch
     * @return int
     */
    public function getTotalFishBoxesSold(string $dateFrom, string $dateTo, ?string $brokerSearch = null): int
    {
        $query = SalesDetails::query()
            ->join('sales', 'sales.id', '=', 'sales_details.sale_id')
            ->join('brokers', 'brokers.id', '=', 'sales.broker_id')
            ->whereIn('sales.status', SalesStatusConstant::getAllActiveStatuses());

        Sales::applyDateRange($query, 'sales.sales_date', $dateFrom, $dateTo);

        // Filter by broker search if provided
        if ($brokerSearch) {
            $brokerNameExpression = $this->brokerNameExpression('brokers');

            $query->where(function ($brokerQuery) use ($brokerSearch, $brokerNameExpression) {
                $brokerQuery->where('brokers.stall_name', 'like', "%{$brokerSearch}%")
                    ->orWhereRaw("{$brokerNameExpression} like ?", ["%{$brokerSearch}%"]);
            });
        }

        return (int) $query->count('sales_details.id');
    }

    /**
     * Get total count of all fish boxes sold (for dashboard)
     * Counts actual fish boxes from sales_details box_id arrays
     *
     * @return int
     */
    public function getTotalFishBoxesSoldCount(): int
    {
        return SalesDetails::count();
    }
}
