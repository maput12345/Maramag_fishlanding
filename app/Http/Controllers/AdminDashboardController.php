<?php

namespace App\Http\Controllers;

use App\Constants\ApplicationStatusConstant;
use App\Constants\OpeningStatusConstant;
use App\Models\BrokerApplication;
use App\Models\ApplicationOpening;
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
        $today = Carbon::today();

        $totalFishBoxesSold = $this->salesRepository->getTotalFishBoxesSold(
            $today->toDateString(),
            $today->toDateString()
        );

        $needsReviewCount = BrokerApplication::query()
            ->where('application_status', ApplicationStatusConstant::SUBMITTED)
            ->count();

        $totalApplicationsCount = BrokerApplication::query()
            ->whereIn('application_status', ApplicationStatusConstant::ongoingReviewStatuses())
            ->count();

        $vacantStallsCount = Stall::query()
            ->whereIn('stall_status', ApplicationOpening::AVAILABLE_STALL_STATUSES)
            ->count();

        $occupiedStallsCount = Stall::query()
            ->where('stall_status', OpeningStatusConstant::STALL_OCCUPIED)
            ->count();

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
