<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\FishPriceRequest;
use App\Models\Broker;
use App\Models\BrokerFishTypeAssignment;
use App\Models\FishPriceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class FishPricesController extends Controller
{
    /**
     * Get data for fish prices tab.
     */
    public function getIndexData(Request $request): array
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $search = trim((string) $request->get('search'));

        $brokerFishTypes = BrokerFishTypeAssignment::with([
                'fishType:id,name',
                'latestPrice' => $this->latestPriceSelect(),
            ])
            ->select(['id', 'broker_id', 'fish_type_id', 'display_name', 'display_description'])
            ->withCount([
                'prices',
                'stockCycles as broker_stock_cycles_count' => function ($stockCycleQuery) use ($brokerId) {
                    $stockCycleQuery->byBroker($brokerId);
                },
            ])
            ->where('broker_id', $brokerId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('display_name', 'like', '%' . $search . '%')
                        ->orWhereHas('fishType', function ($fishTypeQuery) use ($search) {
                            $fishTypeQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(12);
        $brokerFishTypes->getCollection()->load([
            'prices' => function ($query) {
                $query->select([
                        'id',
                        'broker_fish_type_id',
                        'price',
                        'default_cost_price',
                        'price_date',
                        'created_at',
                    ])
                    ->orderByDesc('price_date')
                    ->orderByDesc('id');
            },
        ]);

        $pricingAssignments = collect();

        $editingBrokerFishType = null;
        $historyBrokerFishType = null;

        if ($request->get('modal') === 'create') {
            $pricingAssignments = BrokerFishTypeAssignment::with([
                    'fishType:id,name',
                    'latestPrice' => $this->latestPriceSelect(false),
                ])
                ->select(['id', 'broker_id', 'fish_type_id', 'display_name', 'display_description'])
                ->where('broker_id', $brokerId)
                ->orderByDesc('id')
                ->get();
        }

        if ($request->get('modal') === 'edit' && $request->filled('edit')) {
            $editingBrokerFishType = BrokerFishTypeAssignment::with([
                    'fishType:id,name',
                    'latestPrice' => $this->latestPriceSelect(),
                ])
                ->select(['id', 'broker_id', 'fish_type_id', 'display_name', 'display_description'])
                ->where('broker_id', $brokerId)
                ->find($request->get('edit'));
        }

        if ($request->get('modal') === 'history' && $request->filled('history')) {
            $historyDateFrom = trim((string) $request->get('history_date_from'));
            $historyDateTo = trim((string) $request->get('history_date_to'));

            $historyBrokerFishType = BrokerFishTypeAssignment::with([
                    'fishType:id,name',
                    'prices' => function ($query) use ($historyDateFrom, $historyDateTo) {
                        $query->select([
                                'id',
                                'broker_fish_type_id',
                                'price',
                                'default_cost_price',
                                'price_date',
                                'created_at',
                            ])
                            ->when($historyDateFrom !== '', function ($searchQuery) use ($historyDateFrom) {
                                $searchQuery->whereDate('price_date', '>=', $historyDateFrom);
                            })
                            ->when($historyDateTo !== '', function ($searchQuery) use ($historyDateTo) {
                                $searchQuery->whereDate('price_date', '<=', $historyDateTo);
                            })
                            ->orderByDesc('price_date')
                            ->orderByDesc('id');
                    },
                ])
                ->select(['id', 'broker_id', 'fish_type_id', 'display_name', 'display_description'])
                ->where('broker_id', $brokerId)
                ->find($request->get('history'));
        }

        $priceMetrics = FishPriceRecord::query()
            ->selectRaw('broker_fish_type_id, MAX(price_date) as latest_price_date')
            ->groupBy('broker_fish_type_id');

        $summaryRow = BrokerFishTypeAssignment::query()
            ->leftJoinSub($priceMetrics, 'price_metrics', function ($join) {
                $join->on('BrokerFishTypeAssignment.id', '=', 'price_metrics.broker_fish_type_id');
            })
            ->where('BrokerFishTypeAssignment.broker_id', $brokerId)
            ->selectRaw('
                COUNT(*) as assigned,
                COUNT(price_metrics.broker_fish_type_id) as priced,
                MAX(price_metrics.latest_price_date) as latest_price_date
            ')
            ->first();

        $assignedCount = (int) ($summaryRow?->assigned ?? 0);
        $pricedAssignments = (int) ($summaryRow?->priced ?? 0);

        $priceSummary = [
            'assigned' => $assignedCount,
            'priced' => $pricedAssignments,
            'unpriced' => $assignedCount - $pricedAssignments,
            'latest_price_date' => $summaryRow?->latest_price_date
                ? Carbon::parse($summaryRow->latest_price_date)
                : null,
        ];

        return compact(
            'brokerFishTypes',
            'pricingAssignments',
            'editingBrokerFishType',
            'historyBrokerFishType',
            'priceSummary'
        );
    }

    /**
     * Store a price for a broker fish type assignment.
     */
    public function store(FishPriceRequest $request): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $validated = $request->validated();

        $assignment = $this->findMutableAssignment($brokerId, (int) $validated['broker_fish_type_id']);

        FishPriceRecord::create([
            'broker_fish_type_id' => $assignment->id,
            'price' => $validated['price'],
            'default_cost_price' => $validated['default_cost_price'] ?? null,
            'price_date' => $validated['price_date'],
        ]);

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', 'Fish price saved to history successfully.');
    }

    /**
     * Update the latest price for a broker fish type assignment.
     */
    public function update(FishPriceRequest $request, int $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $validated = $request->validated();

        $assignment = $this->findMutableAssignment($brokerId, $id);

        FishPriceRecord::create([
            'broker_fish_type_id' => $assignment->id,
            'price' => $validated['price'],
            'default_cost_price' => $validated['default_cost_price'] ?? null,
            'price_date' => $validated['price_date'],
        ]);

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', 'Fish price saved to history successfully.');
    }

    /**
     * Delete an unused fish assignment from the price list.
     */
    public function destroy(int $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());

        $assignment = $this->findMutableAssignment($brokerId, $id);

        if (! $assignment->canBeDeletedFromPriceList()) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
                ->with('error', 'This fish has price or stock history, so it cannot be removed from the price list.');
        }

        $assignment->delete();

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', 'Fish removed from the price list successfully.');
    }

    /**
     * Load the broker assignment needed by price mutations.
     */
    private function findMutableAssignment(int $brokerId, int $assignmentId): BrokerFishTypeAssignment
    {
        return BrokerFishTypeAssignment::query()
            ->select(['id', 'broker_id', 'fish_type_id'])
            ->withCount([
                'prices',
                'stockCycles as broker_stock_cycles_count' => function ($stockCycleQuery) use ($brokerId) {
                    $stockCycleQuery->byBroker($brokerId);
                },
            ])
            ->with([
                'latestPrice' => $this->latestPriceSelect(),
            ])
            ->where('broker_id', $brokerId)
            ->findOrFail($assignmentId);
    }

    /**
     * Qualify latest-price columns so latestOfMany joins stay unambiguous on MySQL.
     */
    private function latestPriceSelect(bool $includePriceDate = true): \Closure
    {
        return function ($query) use ($includePriceDate) {
            $columns = [
                'FishPriceRecord.id',
                'FishPriceRecord.broker_fish_type_id',
                'FishPriceRecord.price',
                'FishPriceRecord.default_cost_price',
            ];

            if ($includePriceDate) {
                $columns[] = 'FishPriceRecord.price_date';
            }

            $query->select($columns);
        };
    }
}
