<?php

namespace App\Http\Controllers\Broker;

use App\Constants\FishBoxStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesRequest;
use App\Http\Requests\SalesPaymentRequest;
use App\Models\SalesTransaction;
use App\Models\TransactionLineItem;
use App\Models\PaymentRecord;
use App\Models\FishBox;
use App\Models\BrokerFishTypeAssignment;
use App\Models\FishType;
use App\Constants\SalesStatusConstant;
use App\Models\Broker;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{

    public function getDashboardData(): array
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        $salesToday = PaymentRecord::getTotalSalesToday($brokerId);
        $salesBalance = SalesTransaction::getTotalSalesBalance($brokerId);
        $ordersToday = SalesTransaction::getTotalOrdersToday($brokerId);
        $paidAmountToday = SalesTransaction::getTotalPaidAmountToday($brokerId);
        $paidAmountYesterday = SalesTransaction::getTotalPaidAmountYesterday($brokerId);

        $totalFishBoxes = SalesTransaction::getTotalSoldBoxesToday($brokerId);

        if ($paidAmountYesterday > 0) {
            $growthPercent = (($paidAmountToday - $paidAmountYesterday) / $paidAmountYesterday) * 100;
        } else {
            $growthPercent = 0; // or handle differently if yesterday was 0
        }

        $paidAmountGrowthPercent = round($growthPercent, 2);

        $recentSales = SalesTransaction::getRecentSales(4, $brokerId);
        $dailySalesData = SalesTransaction::getDailySalesLast7Days($brokerId);

        // Get top selling items without date filter (use a very wide date range)
        $allTimeStart = '2020-01-01'; // Use a very early date
        $allTimeEnd = Carbon::now()->format('Y-m-d');
        $topItems = SalesTransaction::getTopSellingItems($brokerId, $allTimeStart, $allTimeEnd, 5, null);

        // Get weekly sales data for this month only
        $thisMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $thisMonthEnd = Carbon::now()->format('Y-m-d');
        $weeklySalesData = SalesTransaction::getDailySalesForPeriod($brokerId, $thisMonthStart, $thisMonthEnd, null);

        return compact('ordersToday', 'salesToday', 'salesBalance',
            'recentSales', 'paidAmountGrowthPercent', 'totalFishBoxes',
            'dailySalesData', 'topItems', 'weeklySalesData');
    }

    /**
     * Get data for sales index
     *
     * @param Request $request
     * @return array
     */
    public function getIndexData(Request $request): array
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        $sales = SalesTransaction::getPaginatedWithFilters($search, $status, $brokerId, $dateFrom, $dateTo);
        $fishBoxes = FishBox::getAvailableForSale($brokerId);
        $allFishTypes = FishType::getFishTypeByBrokerId($brokerId);

        // Filter fish types to only show those with available fish boxes in both create and edit modes
        $availableFishTypeIds = $fishBoxes->pluck('fish_type_id')->unique()->filter()->toArray();
        $fishTypes = $this->prepareFishTypesForForm($allFishTypes->whereIn('id', $availableFishTypeIds));
        $fishPriceMap = BrokerFishTypeAssignment::query()
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
            ->get()
            ->filter(fn (BrokerFishTypeAssignment $assignment): bool => $assignment->latestPrice !== null)
            ->mapWithKeys(function (BrokerFishTypeAssignment $assignment): array {
                return [
                    (string) $assignment->fish_type_id => (float) $assignment->latestPrice->price,
                ];
            })
            ->all();
        $salesSummary = SalesTransaction::getSummaryForFilters($search, $status, $brokerId, $dateFrom, $dateTo);

        $salesStatuses = SalesStatusConstant::getAllStatuses();
        $salesStatusesWithDisplayNames = collect($salesStatuses)->mapWithKeys(function ($status) {
            return [$status => SalesStatusConstant::getDisplayName($status)];
        });
        $salesStatusesWithColorClasses = collect($salesStatuses)->mapWithKeys(function ($status) {
            return [$status => SalesStatusConstant::getStatusColorClasses($status)];
        });

        // Initialize variables
        $editingSales = null;
        $viewingSales = null;
        $saleForPayment = null;
        $printingSales = null;

        // Handle modal-specific sales retrieval
        $editingSales = $this->getModalSales($request, 'edit', 'edit', ['buyer', 'salesDetails.fishBoxPurchase.fishType', 'salesDetails.fishBoxPurchase.fishBox', 'salesPayments']);
        $viewingSales = $this->getModalSales($request, 'show', 'show', ['buyer', 'salesDetails.fishBoxPurchase.fishType', 'salesDetails.fishBoxPurchase.fishBox', 'salesPayments']);
        $saleForPayment = $this->getModalSales($request, 'payment', 'sale');
        $printingSales = $this->getModalSales($request, 'print', 'print', ['buyer', 'salesDetails.fishBoxPurchase.fishType', 'salesDetails.fishBoxPurchase.fishBox', 'salesPayments', 'broker.user', 'broker']);
        // Handle fish boxes for editing mode - only include truly available fish boxes
        if ($editingSales) {
            $fishBoxes = $this->prepareFishBoxesForEdit($fishBoxes, $editingSales);
            $fishTypes = $this->prepareFishTypesForForm($this->prepareFishTypeForEdit($fishTypes, $editingSales));
        }

        // Prepare sales details for the form
        $salesDetails = $this->prepareSalesDetailsForForm($request, $editingSales);

        return compact('sales',
            'fishBoxes', 'fishTypes', 'editingSales',
            'viewingSales', 'salesStatuses',
            'salesStatusesWithDisplayNames', 'salesStatusesWithColorClasses',
            'saleForPayment', 'printingSales', 'salesDetails', 'salesSummary', 'fishPriceMap'
        );
    }

    /**
     * Get only the data needed by the dedicated transaction page.
     *
     * This avoids loading sales lists, summaries, and full FishBox model JSON
     * just to render the create transaction form.
     */
    public function getTransactionData(Request $request): array
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        $fishBoxes = FishBox::getAvailableForSale($brokerId);
        $allFishTypes = FishType::getFishTypeByBrokerId($brokerId);
        $availableFishTypeIds = $fishBoxes->pluck('fish_type_id')->unique()->filter()->toArray();
        $fishTypes = $this->prepareFishTypesForForm($allFishTypes->whereIn('id', $availableFishTypeIds));
        $fishPriceMap = BrokerFishTypeAssignment::query()
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
            ->get()
            ->filter(fn (BrokerFishTypeAssignment $assignment): bool => $assignment->latestPrice !== null)
            ->mapWithKeys(function (BrokerFishTypeAssignment $assignment): array {
                return [
                    (string) $assignment->fish_type_id => (float) $assignment->latestPrice->price,
                ];
            })
            ->all();

        $fishBoxes = $fishBoxes->map(function (FishBox $fishBox): array {
            return [
                'id' => $fishBox->id,
                'name' => $fishBox->name,
                'fish_type_id' => $fishBox->fish_type_id,
            ];
        })->values();

        $editingSales = null;
        $viewingSales = null;
        $saleForPayment = null;
        $printingSales = $this->getModalSales($request, 'print', 'print', ['buyer', 'salesDetails.fishBoxPurchase.fishType', 'salesDetails.fishBoxPurchase.fishBox', 'salesPayments', 'broker.user', 'broker']);
        $salesDetails = $this->prepareSalesDetailsForForm($request, $editingSales);
        $sales = collect();
        $salesSummary = [];
        $salesStatuses = SalesStatusConstant::getAllStatuses();
        $salesStatusesWithDisplayNames = collect($salesStatuses)->mapWithKeys(function ($status) {
            return [$status => SalesStatusConstant::getDisplayName($status)];
        });
        $salesStatusesWithColorClasses = collect($salesStatuses)->mapWithKeys(function ($status) {
            return [$status => SalesStatusConstant::getStatusColorClasses($status)];
        });

        return compact(
            'sales',
            'fishBoxes',
            'fishTypes',
            'editingSales',
            'viewingSales',
            'salesStatuses',
            'salesStatusesWithDisplayNames',
            'salesStatusesWithColorClasses',
            'saleForPayment',
            'printingSales',
            'salesDetails',
            'salesSummary',
            'fishPriceMap'
        );
    }


    public function getAnalyticsData(Request $request): array
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        // Get date filters from request, default to 1st of current month to today
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $status = $request->get('status');

        // Get analytics data using sales history for the selected period.
        $analyticsData = SalesTransaction::getAnalyticsData($brokerId, $dateFrom, $dateTo, $status);

        // Get paginated sales for the period
        $sales = SalesTransaction::getPaginatedWithFilters(
            null,
            $request->get('status'),
            $brokerId,
            $dateFrom,
            $dateTo
        );

        return array_merge($analyticsData, [
            'sales' => $sales,
            'status' => $request->get('status')
        ]);
    }

    /**
     * Store a newly created sale.
     *
     * @param SalesRequest $request
     * @return RedirectResponse
     */
    public function store(SalesRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);


        // Prepare sales data
        $salesData = [
            'sales_date' => $validated['sales_date'],
            'total_amount' => $validated['total_amount'],
            'buyer_first_name' => $validated['buyer_first_name'],
            'buyer_middle_name' => $validated['buyer_middle_name'] ?? null,
            'buyer_last_name' => $validated['buyer_last_name'],
            'buyer_name' => $validated['buyer_name'],
            'buyer_contact' => $validated['buyer_contact'],
        ];

        $salesDetails = $validated['sales_details'] ?? [];
        $initialPayment = $this->extractInitialPaymentData($validated);

        // Create sales with details using the model method
        $sale = SalesTransaction::createSalesWithDetails($salesData, $salesDetails, $brokerId, $initialPayment);

        if ($this->shouldReturnJson($request)) {
            return $this->jsonSuccessResponse('Sale created successfully!', [
                'sale_id' => $sale->id,
                'redirect_url' => $request->input('after_save') === 'transaction'
                    ? route('broker.transaction', [
                        'modal' => 'print',
                        'print' => $sale->id,
                        'auto_print' => 1,
                    ])
                    : route('broker.sales.sales'),
            ]);
        }

        if ($request->input('after_save') === 'transaction') {
            return redirect()->route('broker.transaction', [
                    'modal' => 'print',
                    'print' => $sale->id,
                    'auto_print' => 1,
                ])
                ->with('success', 'Sale created successfully!');
        }

        return redirect()->route('broker.sales.sales')
            ->with('success', 'Sale created successfully!');
    }

    /**
     * Update the specified sale.
     *
     * @param SalesRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(SalesRequest $request, $id): RedirectResponse|JsonResponse
    {
        $sale = SalesTransaction::findOrFail($id);
        $validated = $request->validated();
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        // Check if the sale belongs to the current broker
        if ($sale->broker_id !== $brokerId) {
            if ($this->shouldReturnJson($request)) {
                return $this->jsonErrorResponse('You are not authorized to update this sale.', 403);
            }

            return redirect()->route('broker.sales.sales')
                ->with('error', 'You are not authorized to update this sale.');
        }

        // Prepare sales data
        $salesData = [
            'sales_date' => $validated['sales_date'],
            'total_amount' => $validated['total_amount'],
            'buyer_first_name' => $validated['buyer_first_name'],
            'buyer_middle_name' => $validated['buyer_middle_name'] ?? null,
            'buyer_last_name' => $validated['buyer_last_name'],
            'buyer_name' => $validated['buyer_name'],
            'buyer_contact' => $validated['buyer_contact'],
        ];

        $salesDetails = $validated['sales_details'] ?? [];

        // Update sales with details using the model method
        SalesTransaction::updateSalesWithDetails($sale, $salesData, $salesDetails, $brokerId);

        if ($this->shouldReturnJson($request)) {
            return $this->jsonSuccessResponse('Sale updated successfully!');
        }

        return redirect()->route('broker.sales.sales')
            ->with('success', 'Sale updated successfully!');
    }

    /**
     * Remove the specified sale.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        $sale = SalesTransaction::findOrFail($id);
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        // Check if the sale belongs to the current broker
        if ($sale->broker_id !== $brokerId) {
            if ($this->shouldReturnJson($request)) {
                return $this->jsonErrorResponse('You are not authorized to delete this sale.', 403);
            }

            return redirect()->route('broker.sales.sales')
                ->with('error', 'You are not authorized to delete this sale.');
        }

        DB::transaction(function () use ($sale, $brokerId) {
            // Get sales details before deleting
            $salesDetails = $sale->salesDetails;
            $userId = Auth::user()->id;

            // Reset fish boxes back to IN_STOCK status
            foreach ($salesDetails as $detail) {
                $boxIds = $detail->box_id;

                foreach ($boxIds as $boxId) {

                    FishBox::updateStatus((int) $boxId, FishBoxStatusConstant::IN_STOCK, $userId);
                    InventoryMovement::deleteLogForFishBox((int) $boxId, $sale->created_at);
                }
            }

            $sale->deleteSales();
        });

        if ($this->shouldReturnJson($request)) {
            return $this->jsonSuccessResponse('Sale deleted successfully!');
        }

        return redirect()->route('broker.sales.sales')
            ->with('success', 'Sale deleted successfully!');
    }

    /**
     * Store a newly created sales payment.
     *
     * @param SalesPaymentRequest $request
     * @return RedirectResponse
     */
    public function storePayment(SalesPaymentRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $sale = $brokerId
            ? SalesTransaction::query()->where('broker_id', $brokerId)->find($validated['sales_id'])
            : null;

        if (!$sale) {
            if ($this->shouldReturnJson($request)) {
                return $this->jsonErrorResponse('The selected sale does not belong to your broker account.', 403);
            }

            return redirect()->route('broker.sales.sales')
                ->with('error', 'The selected sale does not belong to your broker account.');
        }

        DB::transaction(function () use ($validated, $sale) {
            // Create the payment
            PaymentRecord::create([
                'sale_id' => $sale->id,
                'paid_amount' => $validated['paid_amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method']
            ]);

            // Update the sales paid amount and status
            $sale->updatePaidAmount();
            $sale->updatePaymentStatus();
        });

        if ($this->shouldReturnJson($request)) {
            return $this->jsonSuccessResponse('Payment recorded successfully!');
        }

        return redirect()->route('broker.sales.sales')
            ->with('success', 'Payment recorded successfully!');
    }

    /**
     * Remove the specified sales payment.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroyPayment(Request $request, $id): RedirectResponse|JsonResponse
    {
        $payment = PaymentRecord::findOrFail($id);
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        // Check if the payment belongs to the current broker
        if ($payment->broker_id !== $brokerId) {
            if ($this->shouldReturnJson($request)) {
                return $this->jsonErrorResponse('You are not authorized to delete this payment.', 403);
            }

            return redirect()->route('broker.sales.sales')
                ->with('error', 'You are not authorized to delete this payment.');
        }

        DB::transaction(function () use ($payment) {
            $sale = $payment->sales;

            $payment->delete();

             // Update the sales paid amount and status
             $sale->updatePaidAmount();
             $sale->updatePaymentStatus();
        });

        if ($this->shouldReturnJson($request)) {
            return $this->jsonSuccessResponse('Payment deleted successfully!');
        }

        return redirect()->route('broker.sales.sales')
            ->with('success', 'Payment deleted successfully!');
    }

    /**
     * Get available fish boxes for sales details
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableFishBoxes(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);
        $fishBoxes = FishBox::getAvailableForSale($brokerId);

        return response()->json($fishBoxes);
    }

    /**
     * Get fish box by QR code for sales
     *
     * @param string $qrCode
     * @return JsonResponse
     */
    public function getFishBoxByQRCode(string $qrCode): JsonResponse
    {
        try {
            // Get broker ID for the current user
            $brokerId = Broker::getBrokerIdByUserId(Auth::id());
            $fishBox = FishBox::getFishBoxByQrCode($qrCode, $brokerId);

            if (!$fishBox) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fish box not found'
                ], 404);
            }

            // Check if fish box is available for sale
            if (
                $fishBox->status !== FishBoxStatusConstant::IN_STOCK
                || !$fishBox->currentPurchase
                || !$fishBox->fish_type_id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fish box is not available for sale'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $fishBox->id,
                    'name' => $fishBox->name,
                    'qr_code' => $fishBox->qr_code,
                    'fish_type_id' => $fishBox->fish_type_id,
                    'fish_type' => $fishBox->fish_type_name,
                    'status' => $fishBox->status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fish box details'
            ], 500);
        }
    }

    /**
     * Get sales record for modal operations with authorization check
     *
     * @param Request $request
     * @param string $modalType
     * @param string $paramName
     * @param array $withRelations
     * @return SalesTransaction|null
     */
    private function getModalSales(Request $request, string $modalType, string $paramName, array $withRelations = []): ?SalesTransaction
    {
        if ($request->get('modal') !== $modalType || !$request->has($paramName)) {
            return null;
        }

        $salesId = $request->get($paramName);
        $query = SalesTransaction::query();

        if (!empty($withRelations)) {
            $query->with($withRelations);
        }

        $sales = $query->find($salesId);

        if (!$sales) {
            return null;
        }

        return $this->authorizeSalesAccess($sales) ? $sales : null;
    }

    /**
     * Check if the current broker has access to the sales record
     *
     * @param SalesTransaction $sales
     * @return bool
     */
    private function authorizeSalesAccess(SalesTransaction $sales): bool
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        return $sales->broker_id === $brokerId;
    }

    /**
     * Prepare fish boxes for editing mode by including already selected boxes
     *
     * @param \Illuminate\Database\Eloquent\Collection $fishBoxes
     * @param SalesTransaction|null $editingSales
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function prepareFishBoxesForEdit($fishBoxes, ?SalesTransaction $editingSales)
    {
        if (!$editingSales || $editingSales->salesDetails->count() === 0) {
            return $fishBoxes;
        }

        $selectedBoxIds = $editingSales->salesDetails->pluck('box_id')->flatten()->unique()->toArray();
        $selectedFishBoxes = FishBox::with('currentPurchase.fishType')->whereIn('id', $selectedBoxIds)->get();
        return $selectedFishBoxes->merge($fishBoxes)->unique('id');
    }

    /**
     * Prepare fish types for editing mode by including already selected boxes
     *
     * @param \Illuminate\Database\Eloquent\Collection $fishTypes
     * @param SalesTransaction|null $editingSales
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function prepareFishTypeForEdit($fishTypes, ?SalesTransaction $editingSales)
    {
        $selectedBoxIds = $editingSales->salesDetails->pluck('box_id')->flatten()->unique()->toArray();
        $selectedFishBoxes = FishBox::whereIn('id', $selectedBoxIds)->get();

        // Get fish type IDs from selected fish boxes
        $selectedFishTypeIds = $selectedFishBoxes->pluck('fish_type_id')->filter()->unique()->toArray();

        // Get fish types using the fish_type_id from selected fish boxes
        $selectedFishTypes = FishType::getFishTypeByBrokerId($editingSales->broker_id)
            ->whereIn('id', $selectedFishTypeIds);

        // Merge with existing fish types and remove duplicates
        return $fishTypes->merge($selectedFishTypes)->unique('id');
    }

    private function prepareFishTypesForForm($fishTypes)
    {
        return $fishTypes->values()->map(function (FishType $fishType): FishType {
            $fishType->setAttribute('name', $fishType->display_name);
            $fishType->setAttribute('description', $fishType->display_description);

            return $fishType;
        });
    }

    /**
     * Prepare sales details for form display
     *
     * @param Request $request
     * @param SalesTransaction|null $editingSales
     * @return array
     */
    private function prepareSalesDetailsForForm(Request $request, ?SalesTransaction $editingSales): array
    {
        if ($request->get('modal') === 'edit' && $editingSales) {
            return $editingSales->salesDetails->map(function($detail) {
                $fishBoxes = $detail->fishBoxes();

                return [
                    'box_id' => $detail->box_id ?? [],
                    'box_labels' => $fishBoxes->map(fn ($fishBox) => $fishBox->name)->values()->all(),
                    'fish_type_id' => (string) ($detail->fishBox?->fish_type_id ?? ''),
                    'item' => $detail->item,
                    'item_description' => $detail->item_description ?? '',
                    'unit_price' => $detail->unit_price ?? '',
                    'discount_mode' => 'percent',
                    'discount' => $detail->discount ?? '',
                    'discount_percent' => ((float) ($detail->unit_price ?? 0) > 0)
                        ? round(((float) ($detail->discount ?? 0) / (float) $detail->unit_price) * 100, 2)
                        : '',
                    'discount_value' => ((float) ($detail->unit_price ?? 0) > 0)
                        ? round(((float) ($detail->discount ?? 0) / (float) $detail->unit_price) * 100, 2)
                        : '',
                    'quantity' => $detail->quantity ?? 1,
                    'sub_total' => $detail->sub_total ?? '',
                ];
            })->toArray();
        }

        return old('sales_details') ?: [
            [
                'box_id' => [],
                'box_labels' => [],
                'fish_type_id' => '',
                'item' => '',
                'item_description' => '',
                'unit_price' => '',
                'discount_mode' => 'percent',
                'discount' => '',
                'discount_percent' => '',
                'discount_value' => '',
                'quantity' => '1',
                'sub_total' => ''
            ]
        ];
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function extractInitialPaymentData(array $validated): ?array
    {
        $paidAmount = $validated['initial_paid_amount'] ?? null;

        if ($paidAmount === null || $paidAmount === '') {
            return null;
        }

        return [
            'paid_amount' => $paidAmount,
            'payment_date' => $validated['initial_payment_date'],
            'payment_method' => $validated['initial_payment_method'],
        ];
    }

    private function jsonSuccessResponse(string $message, array $data = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
        ], $data));
    }

    private function jsonErrorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
