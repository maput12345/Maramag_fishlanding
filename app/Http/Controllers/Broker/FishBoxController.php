<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRestockFishBoxesRequest;
use App\Constants\FishBoxStatusConstant;
use App\Models\FishType;
use App\Models\FishBox;
use App\Http\Requests\FishBoxRequest;
use App\Models\Broker;
use App\Models\BrokerFishTypeAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FishBoxController extends Controller
{
    /**
     * Show the broker's current missing fish box tracking page.
     */
    public function tracking(Request $request): View
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $search = $request->get('search');
        $trackingFishBoxes = FishBox::getBrokerMissingTracking($brokerId, $search, 12, 'tracking_page');
        $missingCount = FishBox::missing()->where('broker_id', $brokerId)->count();

        return view('broker.fish-boxes.tracking', compact(
            'trackingFishBoxes',
            'missingCount',
            'search'
        ));
    }

    /**
     * Get data for fish boxes tab
     *
     * @param Request $request
     * @return array
     */
    public function getIndexData(Request $request): array
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        $fishBoxStatuses = FishBoxStatusConstant::getAllStatuses();
        $fishBoxEditableStatuses = FishBoxStatusConstant::getEditableStatuses();
        $fishTypes = FishType::getFishTypeByBrokerId($brokerId);

        $search = $request->get('search');
        $status = $request->get('status');
        $fishType = $request->get('fish_type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Filter fish boxes by current broker
        $fishBoxes = FishBox::getPaginatedWithFilters($search, $status, $fishType, 12, $brokerId, $dateFrom, $dateTo);
        $fishBoxes->getCollection()->load([
            'purchases' => function ($query) {
                $query->select([
                        'id',
                        'fish_box_id',
                        'fish_type_id',
                        'created_by_user_id',
                        'purchase_date',
                        'cost_price',
                        'created_at',
                    ])
                    ->with(['fishType:id,name,description'])
                    ->with(['inventoryLogs' => function ($logQuery) {
                        $logQuery->orderBy('created_at')->orderBy('id');
                    }])
                    ->orderByDesc('purchase_date')
                    ->orderByDesc('id');
            },
        ]);
        $bulkQrFishBoxes = FishBox::getFilteredForBulkQrPrint($search, $status, $fishType, $brokerId, $dateFrom, $dateTo);
        $fishBoxSummary = FishBox::getStatusSummary($brokerId);
        $fishTypeDefaultCosts = FishBox::getDefaultCostMapForBroker($brokerId);
        $bulkRestockEligibleCount = FishBox::countEligibleForBulkRestock($brokerId);
        $bulkRestockEligibleBoxes = collect();

        $historyFishBox = null;
        $historyFishBoxEvents = collect();

        if ($request && $request->get('modal') === 'bulk-restock') {
            $bulkRestockEligibleBoxes = FishBox::getEligibleForBulkRestock($brokerId);
        }

        if ($request && $request->get('modal') === 'history' && $request->filled('history')) {
            $historyDateFrom = trim((string) $request->get('box_history_date_from'));
            $historyDateTo = trim((string) $request->get('box_history_date_to'));

            $historyFishBox = FishBox::query()
                ->select('FishBox.*')
                ->withBrokerBoxNumber()
                ->with([
                    'purchases' => function ($query) use ($historyDateFrom, $historyDateTo) {
                        $query->select([
                                'id',
                                'fish_box_id',
                                'fish_type_id',
                                'created_by_user_id',
                                'purchase_date',
                                'cost_price',
                                'created_at',
                            ])
                            ->with(['fishType:id,name,description'])
                            ->when($historyDateFrom !== '', function ($searchQuery) use ($historyDateFrom) {
                                $searchQuery->whereDate('purchase_date', '>=', $historyDateFrom);
                            })
                            ->when($historyDateTo !== '', function ($searchQuery) use ($historyDateTo) {
                                $searchQuery->whereDate('purchase_date', '<=', $historyDateTo);
                            })
                            ->orderByDesc('purchase_date')
                            ->orderByDesc('id');
                    },
                ])
                ->where('broker_id', $brokerId)
                ->find($request->get('history'));

            if ($historyFishBox) {
                $historyFishBoxEvents = $this->buildFishBoxHistoryEvents(
                    $historyFishBox,
                    $historyDateFrom,
                    $historyDateTo
                );
            }
        }

        return compact(
            'fishBoxStatuses',
            'fishBoxEditableStatuses',
            'fishTypes',
            'fishBoxes',
            'fishBoxSummary',
            'bulkQrFishBoxes',
            'fishTypeDefaultCosts',
            'bulkRestockEligibleCount',
            'bulkRestockEligibleBoxes',
            'historyFishBox',
            'historyFishBoxEvents'
        );
    }

    private function buildFishBoxHistoryEvents(FishBox $fishBox, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Support\Collection
    {
        $dateFrom = trim((string) $dateFrom);
        $dateTo = trim((string) $dateTo);
        $events = collect();

        $createdAt = $fishBox->created_at;
        if ($createdAt && $this->historyDateMatches($createdAt, $dateFrom, $dateTo)) {
            $events->push([
                'date' => $createdAt,
                'event' => 'Created',
                'status' => FishBoxStatusConstant::UNASSIGNED,
                'fish' => 'Unassigned',
                'cost' => null,
                'details' => 'Reusable box profile created.',
            ]);
        }

        $purchases = $fishBox->purchases()
            ->with([
                'fishType:id,name,description',
                'inventoryLogs' => function ($query) use ($dateFrom, $dateTo) {
                    if ($dateFrom !== '') {
                        $query->whereDate('created_at', '>=', $dateFrom);
                    }

                    if ($dateTo !== '') {
                        $query->whereDate('created_at', '<=', $dateTo);
                    }

                    $query->orderBy('created_at')->orderBy('id');
                },
            ])
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();

        foreach ($purchases as $purchase) {
            $fishName = BrokerFishTypeAssignment::resolveDisplayName($fishBox->broker_id, $purchase->fishType) ?? 'Unassigned';
            $hasStockedLog = $purchase->inventoryLogs->contains('status', FishBoxStatusConstant::IN_STOCK);

            if (!$hasStockedLog && $purchase->purchase_date && $this->historyDateMatches($purchase->purchase_date, $dateFrom, $dateTo)) {
                $events->push([
                    'date' => $purchase->purchase_date,
                    'event' => 'Stocked',
                    'status' => FishBoxStatusConstant::IN_STOCK,
                    'fish' => $fishName,
                    'cost' => $purchase->cost_price,
                    'details' => 'Stock cycle recorded.',
                ]);
            }

            foreach ($purchase->inventoryLogs as $movement) {
                $events->push([
                    'date' => $movement->created_at,
                    'event' => FishBoxStatusConstant::label($movement->status),
                    'status' => $movement->status,
                    'fish' => $fishName,
                    'cost' => $purchase->cost_price,
                    'details' => $this->historyMovementDescription($movement->status),
                ]);
            }
        }

        return $events
            ->sortByDesc(fn (array $event) => $event['date']?->timestamp ?? 0)
            ->values();
    }

    private function historyDateMatches($date, string $dateFrom, string $dateTo): bool
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        if ($dateFrom !== '' && $date->lt(Carbon::parse($dateFrom)->startOfDay())) {
            return false;
        }

        if ($dateTo !== '' && $date->gt(Carbon::parse($dateTo)->endOfDay())) {
            return false;
        }

        return true;
    }

    private function historyMovementDescription(string $status): string
    {
        return match ($status) {
            FishBoxStatusConstant::IN_STOCK => 'Marked available for sales.',
            FishBoxStatusConstant::SOLD => 'Used in a sales transaction.',
            FishBoxStatusConstant::RETURNED => 'Returned to the broker.',
            FishBoxStatusConstant::MISSING => 'Marked missing for tracking.',
            FishBoxStatusConstant::RETIRED => 'Marked damaged.',
            default => 'Status updated.',
        };
    }

    /**
     * Store a newly created fish box.
     *
     * @param FishBoxRequest $request
     * @return RedirectResponse
     */
    public function store(FishBoxRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());

        $createdBoxes = FishBox::createEmptyBoxes(
            $validated['quantity'],
            $brokerId
        );

        $message = count($createdBoxes) === 1
            ? 'Box created successfully!'
            : count($createdBoxes) . ' boxes created successfully!';

        return redirect()->route('broker.inventory.index', ['tab' => 'fishBoxes'])
            ->with('success', $message);
    }

    /**
     * Update the specified fish box.
     *
     * @param FishBoxRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(FishBoxRequest $request, $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishBox = FishBox::getFishBoxByIdAndBroker($id, $brokerId);
        $validated = $request->validated();
        $userId = Auth::id();
        $costPrice = $this->resolveCostPriceForBroker(
            $brokerId,
            (int) $validated['fish_type_id'],
            $validated['cost_price'] ?? null,
            $fishBox->currentPurchase?->cost_price !== null ? (float) $fishBox->currentPurchase->cost_price : null
        );

        if ($costPrice === null) {
            return $this->redirectMissingCostPrice();
        }

        $validated['cost_price'] = $costPrice;

        if ($this->wouldOverwriteHistoricalPurchase($fishBox, $validated)) {
            return redirect()->back()
                ->with('error', 'This fish box already has sales history. Use Bulk Assign / Daily Restock to start a new daily stock record instead of editing the old one.')
                ->withInput();
        }

        $fishBox->updateBoxAndPurchase($validated, $userId);

        return redirect()->route('broker.inventory.index', ['tab' => 'fishBoxes'])
            ->with('success', 'Fish box updated successfully!');
    }

    /**
     * Remove the specified fish box.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishBox = FishBox::getFishBoxByIdAndBroker($id, $brokerId);
        $returnToFishBoxes = redirect()->to(
            $request->headers->get('referer') ?: route('broker.inventory.index', ['tab' => 'fishBoxes'])
        );

        if ($fishBox->canBeRetired()) {
            FishBox::updateStatus($fishBox->id, FishBoxStatusConstant::RETIRED, Auth::id());

            return $returnToFishBoxes
                ->with('success', 'Fish box marked as damaged successfully. Its history remains available for receipts and reports.');
        }

        if (!$fishBox->canBeDeleted()) {
            return $returnToFishBoxes
                ->with('error', 'This fish box has history or is still active. Return or clear it before retiring; historical boxes are not deleted.');
        }

        $fishBox->delete();

        return $returnToFishBoxes
            ->with('success', 'Fish box deleted successfully!');
    }

    /**
     * Return fish box via QR code scanning
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function returnFishBoxViaQr(Request $request): JsonResponse
    {
        // Validate the request - expect text QR code from camera scanning
        $request->validate([
            'qr_code' => 'required|string|max:255',
        ]);

        $qrCodeValue = $request->input('qr_code');

        try {
            $brokerId = Broker::getBrokerIdByUserId(Auth::id());
            // Get fish box by QR code with broker validation
            $fishBox = FishBox::getFishBoxByQrCode($qrCodeValue, $brokerId);

            // Check if the fish box is found
            if (!$fishBox) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code. Fish box not found or not assigned to you.'
                ], 404);
            }

            // Check if the fish box is already returned
            if($fishBox->status == FishBoxStatusConstant::RETURNED) {
                return response()->json([
                    'success' => false,
                    'message' => 'This fish box is already returned.'
                ], 400);
            }

            if (!$fishBox->canBeReturned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only sold or missing fish boxes can be returned.'
                ], 400);
            }

            $newStatus = FishBoxStatusConstant::RETURNED;
            // Update the fish box status based on current status
            FishBox::updateStatus($fishBox->id, $newStatus, Auth::id());

            // Return JSON response for AJAX requests
            return response()->json([
                'success' => true,
                'message' => "'{$fishBox->name}' status updated to '{$newStatus}' successfully.",
                'data' => [
                    'fish_box_id' => $fishBox->id,
                    'fish_box_name' => $fishBox->name,
                    'old_status' => $fishBox->status,
                    'new_status' => $newStatus
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('QR Code processing error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark fish box as missing
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function markAsMissing($id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishBox = FishBox::getFishBoxByIdAndBroker($id, $brokerId);

        if (!$fishBox->canBeMarkedAsMissing()) {
            return redirect()->back()
                ->with('error', 'This fish box cannot be marked as missing.');
        }

        FishBox::updateStatus($fishBox->id, FishBoxStatusConstant::MISSING, Auth::id());

        return redirect()->back()
            ->with('success', 'Fish box marked as missing successfully!');
    }

    /**
     * Restore a retired fish box so it can be restocked again.
     */
    public function restoreRetired($id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishBox = FishBox::getFishBoxByIdAndBroker($id, $brokerId);

        if (!$fishBox->canBeRestored()) {
            return redirect()->back()
                ->with('error', 'Damaged fish boxes cannot be restored for restocking.');
        }

        FishBox::updateStatus($fishBox->id, FishBoxStatusConstant::UNASSIGNED, Auth::id());

        return redirect()->back()
            ->with('success', 'Fish box restored successfully. It is now available for restocking.');
    }

    /**
     * Return fish box
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function returnFishBox($id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishBox = FishBox::getFishBoxByIdAndBroker($id, $brokerId);

        if (!$fishBox->canBeReturned()) {
            return redirect()->back()
                ->with('error', 'Only sold or missing fish boxes can be returned.');
        }

        FishBox::updateFishBoxesForReturned($fishBox->id, Auth::id());

        return redirect()->back()
            ->with('success', 'Fish box returned successfully!');
    }

    /**
     * Return all returned fish boxes to stock
     *
     * @return RedirectResponse
     */
    public function returnToStock(): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $returnedCount = FishBox::returnAllToStock($brokerId, Auth::id());

        if ($returnedCount === 0) {
            return redirect()->back()
                ->with('info', 'No returned fish boxes to process.');
        }

        return redirect()->back()
            ->with('success', "{$returnedCount} returned fish boxes cleared and marked as unassigned.");
    }

    /**
     * Bulk assign fish type and daily cost to reusable fish boxes.
     */
    public function bulkRestock(BulkRestockFishBoxesRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);
        $costPrice = $this->resolveCostPriceForBroker(
            $brokerId,
            (int) $validated['fish_type_id'],
            null
        );

        if ($costPrice === null) {
            return $this->redirectMissingCostPrice();
        }

        $restockedCount = FishBox::bulkRestock(
            $brokerId,
            array_map('intval', $validated['fish_box_ids']),
            (int) $validated['fish_type_id'],
            $costPrice,
            $userId
        );

        if ($restockedCount === 0) {
            return redirect()->back()
                ->with('error', 'No eligible fish boxes were selected for daily restocking.')
                ->withInput();
        }

        $message = $restockedCount === 1
            ? 'Fish box restocked successfully.'
            : "{$restockedCount} fish boxes restocked successfully.";

        return redirect()->route('broker.inventory.index', $this->fishBoxReturnQuery($request))
            ->with('success', $message);
    }

    /**
     * Keep the broker on the same fish-box list page after an action.
     */
    private function fishBoxReturnQuery(Request $request): array
    {
        return array_filter([
            'tab' => 'fishBoxes',
            'page' => $request->query('page'),
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'fish_type' => $request->query('fish_type'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Resolve a restock cost from manual input or the fish-price default.
     */
    private function resolveCostPriceForBroker(
        int $brokerId,
        int $fishTypeId,
        mixed $submittedCostPrice,
        ?float $fallbackCostPrice = null
    ): ?float {
        if ($submittedCostPrice !== null && $submittedCostPrice !== '') {
            return (float) $submittedCostPrice;
        }

        $defaultCostPrice = FishBox::getDefaultCostPriceForBrokerFishType($brokerId, $fishTypeId);

        if ($defaultCostPrice !== null) {
            return $defaultCostPrice;
        }

        return $fallbackCostPrice;
    }

    /**
     * Redirect back when neither a manual nor default cost price is available.
     */
    private function redirectMissingCostPrice(): RedirectResponse
    {
        return redirect()->back()
            ->withErrors([
                'cost_price' => 'Set today\'s stock cost in Fish Prices before assigning stock.',
            ])
            ->withInput();
    }

    /**
     * Prevent manual edits from rewriting purchase rows already referenced by sales history.
     */
    private function wouldOverwriteHistoricalPurchase(FishBox $fishBox, array $validated): bool
    {
        $purchase = $fishBox->currentPurchase;

        if (!$purchase || !$fishBox->currentPurchaseHasSalesHistory()) {
            return false;
        }

        $fishTypeChanged = isset($validated['fish_type_id'])
            && (int) $validated['fish_type_id'] !== (int) $purchase->fish_type_id;

        $incomingCost = isset($validated['cost_price']) ? number_format((float) $validated['cost_price'], 2, '.', '') : null;
        $currentCost = $purchase->cost_price !== null ? number_format((float) $purchase->cost_price, 2, '.', '') : null;
        $costChanged = $incomingCost !== null && $incomingCost !== $currentCost;

        return $fishTypeChanged || $costChanged;
    }
}
