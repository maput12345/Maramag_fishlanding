<?php

namespace App\Http\Controllers;

use App\Models\BrokerApplication;
use App\Models\InventoryMovement;
use App\Models\Stall;
use App\Repositories\SalesRepository;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    protected $salesRepository;

    public function __construct(SalesRepository $salesRepository)
    {
        $this->salesRepository = $salesRepository;
    }

    public function index()
    {
        // Count total fish boxes sold from sales_details
        $totalFishBoxesSold = $this->salesRepository->getTotalFishBoxesSoldCount();

        $needsReviewCount = BrokerApplication::query()
            ->where('application_status', 'Submitted')
            ->count();

        $totalApplicationsCount = BrokerApplication::query()
            ->whereIn('application_status', [
                'Submitted',
                'Pending',
                'Needs Review',
                'Under Review',
                'Revision Resubmitted',
                'For Revision',
            ])
            ->count();

        $vacantStallsCount = Stall::query()
            ->where('stall_status', 'Vacant')
            ->count();

        $occupiedStallsCount = Stall::query()
            ->where('stall_status', 'Occupied')
            ->count();

        $today = Carbon::today();
        $weekStart = $today->copy()->subDays(6);

        $topBrokersDaily = $this->salesRepository->getTopBrokersForAdmin(
            $today->toDateString(),
            $today->toDateString(),
            null,
            4
        );

        $topBrokersWeekly = $this->salesRepository->getTopBrokersForAdmin(
            $weekStart->toDateString(),
            $today->toDateString(),
            null,
            4
        );

        $topFishDaily = InventoryMovement::getTopFishTypesSoldForAdmin(
            $today->toDateString(),
            $today->toDateString(),
            null,
            4
        );

        $topFishWeekly = InventoryMovement::getTopFishTypesSoldForAdmin(
            $weekStart->toDateString(),
            $today->toDateString(),
            null,
            4
        );

        return view('admin.dashboard', compact(
            'totalFishBoxesSold',
            'needsReviewCount',
            'totalApplicationsCount',
            'vacantStallsCount',
            'occupiedStallsCount',
            'topBrokersDaily',
            'topBrokersWeekly',
            'topFishDaily',
            'topFishWeekly'
        ));
    }
}
