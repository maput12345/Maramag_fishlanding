<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Broker\SalesController;
use App\Models\FishBox;
use App\Models\Broker;
use App\Models\FishType;
use App\Constants\FishBoxStatusConstant;
use App\Models\InventoryMovement;
use App\Repositories\SalesRepository;
use App\Repositories\InventoryRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SalesManagementController extends Controller
{
    protected $salesRepository;
    protected $inventoryRepository;

    public function __construct(SalesRepository $salesRepository, InventoryRepository $inventoryRepository)
    {
        $this->salesRepository = $salesRepository;
        $this->inventoryRepository = $inventoryRepository;
    }

    /**
     * @param Request $request
     *
     * @return View
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->get('tab') === 'fishbox-tracking') {
            return redirect()->route('admin.sales.tracking', $request->except('tab'));
        }

        $data = $this->getAnalysisData($request);

        return view('admin.sales.index', $data);
    }

    /**
     * Show the dedicated admin fish box tracking page.
     */
    public function fishboxTracking(Request $request): View|RedirectResponse
    {
        if (!$request->user()?->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Only admin accounts can access fish box tracking.');
        }

        $data = $this->getFishboxTrackingData($request);

        return view('admin.sales.tracking', $data);
    }


    /**
     * @param Request $request
     *
     * @return array
     */
    private function getAnalysisData(Request $request): array
    {
        // Get date filters from request, default to 1st of current month to today
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $brokerSearch = $request->get('broker_search');
        $receiptDate = $dateTo;

        // Get all brokers with their sales within the date range
        $brokersWithSales = $this->salesRepository->getBrokersWithSalesDetails($dateFrom, $dateTo, $brokerSearch);

        // Calculate total fishboxes sold based on filters
        $totalFishBoxesSold = $this->salesRepository->getTotalFishBoxesSold($dateFrom, $dateTo, $brokerSearch);

        return compact(
            'brokersWithSales',
            'totalFishBoxesSold',
            'dateFrom',
            'dateTo',
            'brokerSearch',
            'receiptDate'
        );
    }

    public function analytics(Request $request)
    {
        $salesController = new SalesController();
        $data = $salesController->getAnalyticsData($request);
        return view('broker.sales.analytics-polished', $data);
    }

    public function sales(Request $request)
    {
        $salesController = new SalesController();
        $data = $salesController->getIndexData($request);
        return view('broker.sales.sales-polished', $data);
    }

    public function transaction(Request $request)
    {
        if ($request->get('modal') !== 'print') {
            $request->merge(['modal' => 'create']);
        }

        $salesController = new SalesController();
        $data = $salesController->getTransactionData($request);

        return view('broker.sales.transaction', $data);
    }

    /**
     * Return a fresh broker receipt snapshot for admin printing.
     */
    public function brokerReceiptData(Request $request, Broker $broker): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $snapshot = $this->salesRepository->getBrokerReceiptSnapshot($broker->id, $dateFrom, $dateTo);

        if (!$snapshot) {
            return response()->json([
                'message' => 'Broker receipt data not found.',
            ], 404);
        }

        return response()->json($snapshot);
    }

    /**
     * Get fishbox tracking data for admin
     *
     * @param Request $request
     * @return array
     */
    private function getFishboxTrackingData(Request $request): array
    {
        // Get available actions for filter
        $actions = FishBoxStatusConstant::getStatusOnlyForAdmin();

        // Get filter parameters
        $action = $request->get('action');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $currentStatusSummary = FishBox::getStatusSummary();
        $summary = [
            'returned' => $currentStatusSummary['returned'],
            'missing' => $currentStatusSummary['missing'],
        ];

        $trackedFishBoxes = FishBox::getAdminTrackingStatuses($action, $dateFrom, $dateTo, 12, 'tracking_page');
        $inventoryLogs = InventoryMovement::getPaginatedWithFilters($action, $dateFrom, $dateTo, 12, 'history_page');

        return [
            'summary' => $summary,
            'actions' => $actions,
            'trackedFishBoxes' => $trackedFishBoxes,
            'inventoryLogs' => $inventoryLogs,
        ];
    }

}
