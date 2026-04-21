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

class FishPricesController extends Controller
{
    /**
     * Get data for fish prices tab.
     */
    public function getIndexData(Request $request): array
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $search = trim((string) $request->get('search'));

        $brokerFishTypes = BrokerFishType::with(['fishType', 'latestPrice'])
            ->where('broker_id', $brokerId)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('fishType', function ($fishTypeQuery) use ($search) {
                    $fishTypeQuery->where('name', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(12);

        $pricingAssignments = BrokerFishType::with(['fishType', 'latestPrice'])
            ->where('broker_id', $brokerId)
            ->orderByDesc('id')
            ->get();

        $editingBrokerFishType = null;

        if ($request->get('modal') === 'edit' && $request->filled('edit')) {
            $editingBrokerFishType = BrokerFishType::with(['fishType', 'latestPrice'])
                ->where('broker_id', $brokerId)
                ->find($request->get('edit'));
        }

        $pricedAssignments = $pricingAssignments->filter(fn ($assignment) => $assignment->latestPrice !== null)->count();
        $latestPrice = FishPrice::whereHas('brokerFishType', function ($query) use ($brokerId) {
            $query->where('broker_id', $brokerId);
        })->latest('price_date')->latest('id')->first();

        $priceSummary = [
            'assigned' => $pricingAssignments->count(),
            'priced' => $pricedAssignments,
            'unpriced' => $pricingAssignments->count() - $pricedAssignments,
            'latest_price_date' => $latestPrice?->price_date,
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

        $assignment = BrokerFishType::with(['fishType', 'latestPrice'])
            ->where('broker_id', $brokerId)
            ->findOrFail($validated['broker_fish_type_id']);

        $latestPrice = $assignment->latestPrice;

        if ($latestPrice) {
            $latestPrice->update([
                'price' => $validated['price'],
                'price_date' => $validated['price_date'],
            ]);

            $message = 'Fish price updated successfully.';
        } else {
            FishPrice::create([
                'broker_fish_type_id' => $assignment->id,
                'price' => $validated['price'],
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

        $assignment = BrokerFishType::with(['fishType', 'latestPrice'])
            ->where('broker_id', $brokerId)
            ->findOrFail($id);

        if ($assignment->latestPrice) {
            $assignment->latestPrice->update([
                'price' => $validated['price'],
                'price_date' => $validated['price_date'],
            ]);
        } else {
            FishPrice::create([
                'broker_fish_type_id' => $assignment->id,
                'price' => $validated['price'],
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

        $assignment = BrokerFishType::with(['fishType', 'latestPrice'])
            ->where('broker_id', $brokerId)
            ->findOrFail($id);

        if (!$assignment->latestPrice) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
                ->with('error', 'No current fish price was found for that fish type.');
        }

        $assignment->latestPrice->delete();

        return redirect()->route('broker.inventory.index', ['tab' => 'fishPrices'])
            ->with('success', 'Fish price removed successfully.');
    }
}
