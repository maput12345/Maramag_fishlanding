<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\FishPriceRequest;
use App\Models\Broker;
use App\Models\BrokerFishType;
use App\Models\FishPrice;
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

        $brokerFishTypes = BrokerFishType::with([
                'fishType:id,name',
                'latestPrice' => $this->latestPriceSelect(),
            ])
            ->select(['id', 'broker_id', 'fish_type_id'])
            ->where('broker_id', $brokerId)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('fishType', function ($fishTypeQuery) use ($search) {
                    $fishTypeQuery->where('name', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(12);

        $pricingAssignments = collect();

        $editingBrokerFishType = null;

        if ($request->get('modal') === 'create') {
            $pricingAssignments = BrokerFishType::with([
                    'fishType:id,name',
                    'latestPrice' => $this->latestPriceSelect(false),
                ])
                ->select(['id', 'broker_id', 'fish_type_id'])
                ->where('broker_id', $brokerId)
                ->orderByDesc('id')
                ->get();
        }

        if ($request->get('modal') === 'edit' && $request->filled('edit')) {
            $editingBrokerFishType = BrokerFishType::with([
                    'fishType:id,name',
                    'latestPrice' => $this->latestPriceSelect(),
                ])
                ->select(['id', 'broker_id', 'fish_type_id'])
                ->where('broker_id', $brokerId)
                ->find($request->get('edit'));
        }

        $priceMetrics = FishPrice::query()
            ->selectRaw('broker_fish_type_id, MAX(price_date) as latest_price_date')
            ->groupBy('broker_fish_type_id');

        $summaryRow = BrokerFishType::query()
            ->leftJoinSub($priceMetrics, 'price_metrics', function ($join) {
                $join->on('broker_fish_type.id', '=', 'price_metrics.broker_fish_type_id');
            })
            ->where('broker_fish_type.broker_id', $brokerId)
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

        $latestPrice = $assignment->latestPrice;

        if ($latestPrice) {
            $latestPrice->update([
                'price' => $validated['price'],
                'default_cost_price' => $validated['default_cost_price'] ?? null,
                'price_date' => $validated['price_date'],
            ]);

            $message = 'Fish price updated successfully.';
        } else {
            FishPrice::create([
                'broker_fish_type_id' => $assignment->id,
                'price' => $validated['price'],
                'default_cost_price' => $validated['default_cost_price'] ?? null,
                'price_date' => $validated['price_date'],
            ]);

            $message = 'Fish price created successfully.';
        }

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', $message);
    }

    /**
     * Update the latest price for a broker fish type assignment.
     */
    public function update(FishPriceRequest $request, int $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $validated = $request->validated();

        $assignment = $this->findMutableAssignment($brokerId, $id);

        if ($assignment->latestPrice) {
            $assignment->latestPrice->update([
                'price' => $validated['price'],
                'default_cost_price' => $validated['default_cost_price'] ?? null,
                'price_date' => $validated['price_date'],
            ]);
        } else {
            FishPrice::create([
                'broker_fish_type_id' => $assignment->id,
                'price' => $validated['price'],
                'default_cost_price' => $validated['default_cost_price'] ?? null,
                'price_date' => $validated['price_date'],
            ]);
        }

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', 'Fish price updated successfully.');
    }

    /**
     * Delete the current price for a broker fish type assignment.
     */
    public function destroy(int $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());

        $assignment = $this->findMutableAssignment($brokerId, $id);

        if (!$assignment->latestPrice) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
                ->with('error', 'No current fish price was found for that fish type.');
        }

        $assignment->latestPrice->delete();

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', 'Fish price removed successfully.');
    }

    /**
     * Load the broker assignment needed by price mutations.
     */
    private function findMutableAssignment(int $brokerId, int $assignmentId): BrokerFishType
    {
        return BrokerFishType::query()
            ->select(['id', 'broker_id', 'fish_type_id'])
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
                'fish_prices.id',
                'fish_prices.broker_fish_type_id',
                'fish_prices.price',
                'fish_prices.default_cost_price',
            ];

            if ($includePriceDate) {
                $columns[] = 'fish_prices.price_date';
            }

            $query->select($columns);
        };
    }
}
