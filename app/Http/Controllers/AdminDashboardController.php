<?php

namespace App\Http\Controllers;

use App\Models\FishBox;
use App\Models\Broker;
use App\Models\User;
use App\Constants\FishBoxStatusConstant;
use App\Constants\InventoryLogActionConstant;
use App\Models\InventoryMovement;
use App\Repositories\SalesRepository;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    protected $salesRepository;

    public function __construct(SalesRepository $salesRepository)
    {
        $this->salesRepository = $salesRepository;
    }

    public function index()
    {
        // Get total brokers count
        $totalBrokers = Broker::count();

        // Count total fish boxes sold from sales_details
        $totalFishBoxesSold = $this->salesRepository->getTotalFishBoxesSoldCount();

        // Count fish boxes with current status Missing (net missing count)
        $totalFishBoxesMissing = FishBox::missing()->count();
        
        // Count fish boxes with current status Returned
        $totalFishBoxesReturned = FishBox::returned()->count();

        // Get top brokers with fishbox count
        $topBrokers = $this->salesRepository->getTopBrokersWithFishBoxCount();

        // Get top fish types sold
        $topFishTypes = InventoryMovement::getTopFishTypesSold();

        // Get current missing boxes with broker ownership
        $currentMissingBoxes = FishBox::getCurrentMissingBoxes();

        return view('admin.dashboard', compact(
            'totalBrokers',
            'totalFishBoxesSold',
            'totalFishBoxesMissing',
            'totalFishBoxesReturned',
            'topBrokers',
            'topFishTypes',
            'currentMissingBoxes'
        ));
    }
}
