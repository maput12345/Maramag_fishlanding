<?php

namespace App\Repositories;

use App\Constants\FishBoxStatusConstant;
use App\Models\Sales;
use App\Models\Broker;
use App\Models\InventoryLog;
use App\Constants\SalesStatusConstant;
use App\Models\FishBox;
use App\Models\SalesDetails;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesRepository
{


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
        $query = Sales::with(['broker', 'salesPayments'])
            ->active()
            ->whereDate('sales_date', '>=', $dateFrom)
            ->whereDate('sales_date', '<=', $dateTo);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()
            ->groupBy('broker_id')
            ->map(function ($sales) {
                $firstSale = $sales->first();

                return [
                    'broker' => $firstSale?->broker,
                    'sales_count' => $sales->count(),
                    'total_sales' => $sales->sum(fn ($sale) => $sale->paid_amount),
                ];
            })
            ->sortByDesc('sales_count')
            ->take($limit)
            ->values();
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

        for ($i = 0; $i < $chartDays; $i++) {
            $date = $chartStartDate->copy()->addDays($i);
            $dayName = $date->format('D');

            $query = Sales::with('salesPayments')
                ->active()
                ->whereDate('sales_date', $date->format('Y-m-d'));

            if ($status) {
                $query->where('status', $status);
            }

            $sales = $query->get()->sum(fn ($sale) => $sale->paid_amount);

            $dailySales[] = [
                'label' => $dayName,
                'value' => (float) $sales
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
        $query = Sales::active()
            ->whereDate('sales_date', '>=', $dateFrom)
            ->whereDate('sales_date', '<=', $dateTo);

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
        $query = Sales::active()
            ->whereDate('sales_date', '>=', $dateFrom)
            ->whereDate('sales_date', '<=', $dateTo);

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
        $query = Sales::with(['broker.user', 'salesDetails'])
            ->active()
            ->whereDate('sales_date', '>=', $dateFrom)
            ->whereDate('sales_date', '<=', $dateTo);

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

        foreach ($salesStatuses as $statusValue) {
            $statusQuery = Sales::with('salesPayments')->active()->whereBetween('sales_date', [$dateFrom, $dateTo]);
            if ($status) {
                $statusQuery->where('status', $status);
            }

            $sales = (clone $statusQuery)->where('status', $statusValue)->get();
            $count = $sales->count();
            $totalAmount = $sales->sum(fn ($sale) => $sale->paid_amount);

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

        $brokerSalesData = Sales::with(['broker', 'salesDetails', 'salesPayments'])
            ->active()
            ->whereBetween('sales_date', [$startOfMonth, $endOfMonth])
            ->get();

        return $brokerSalesData
            ->groupBy('broker_id')
            ->map(function ($sales) {
                $firstSale = $sales->first();

                return [
                    'broker' => $firstSale?->broker,
                    'sales_count' => $sales->count(),
                    'total_sales' => $sales->sum(fn ($sale) => $sale->paid_amount),
                    'fishbox_count' => $sales->sum(fn ($sale) => $sale->salesDetails->count()),
                ];
            })
            ->sortByDesc('sales_count')
            ->take(5)
            ->values()
            ->map(function ($brokerData) {
            return [
                'broker' => $brokerData['broker'],
                'sales_count' => $brokerData['sales_count'],
                'total_sales' => $brokerData['total_sales'],
                'fishbox_count' => $brokerData['fishbox_count']
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
        $brokersQuery = Broker::with([
            'user',
            'sales' => function ($query) use ($dateFrom, $dateTo) {
                $query->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
                    ->whereDate('sales_date', '>=', $dateFrom)
                    ->whereDate('sales_date', '<=', $dateTo)
                    ->with('salesDetails')
                    ->orderBy('sales_date', 'desc');
            }
        ])
        // Only get brokers who have sales in the date range
        ->whereHas('sales', function ($query) use ($dateFrom, $dateTo) {
            $query->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
                ->whereDate('sales_date', '>=', $dateFrom)
                ->whereDate('sales_date', '<=', $dateTo);
        });

        // Apply broker search filter if provided
        if ($brokerSearch) {
            $brokersQuery->where(function ($query) use ($brokerSearch) {
                $query->where('stall_name', 'like', "%{$brokerSearch}%")
                    ->orWhereRaw(
                        "TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) like ?",
                        ["%{$brokerSearch}%"]
                    );
            });
        }

        $brokersQuery->orderBy('stall_name', 'asc');

        // Paginate the brokers (10 per page)
        return $brokersQuery->paginate(10);
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
        $query = Sales::whereIn('status', SalesStatusConstant::getAllActiveStatuses())
            ->whereDate('sales_date', '>=', $dateFrom)
            ->whereDate('sales_date', '<=', $dateTo);

        // Filter by broker search if provided
        if ($brokerSearch) {
            $query->whereHas('broker', function ($brokerQuery) use ($brokerSearch) {
                $brokerQuery->where('stall_name', 'like', "%{$brokerSearch}%")
                    ->orWhereRaw(
                        "TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) like ?",
                        ["%{$brokerSearch}%"]
                    );
            });
        }

        return $query->with('salesDetails')->get()
            ->sum(fn ($sale) => $sale->salesDetails->count());
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
