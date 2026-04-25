<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\FishTypeRequest;
use App\Models\BrokerFishType;
use App\Models\FishType;
use App\Models\Broker;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class FishTypesController extends Controller
{
    /**
     * Get data for fish types tab
     *
     * @param Request $request
     * @return array
     */
    public function getIndexData(Request $request): array
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        $fishTypes = FishType::getPaginatedWithSearch($request->get('search'), $brokerId);
        $fishTypeSummary = [
            'assigned' => BrokerFishType::where('broker_id', $brokerId)->count(),
            'with_prices' => BrokerFishType::where('broker_id', $brokerId)->whereHas('latestPrice')->count(),
            'used' => BrokerFishType::where('broker_id', $brokerId)
                ->whereHas('fishType.fishBoxes', function ($purchaseQuery) use ($brokerId) {
                    $purchaseQuery->whereHas('fishBox', function ($fishBoxQuery) use ($brokerId) {
                        $fishBoxQuery->where('broker_id', $brokerId);
                    });
                })
                ->count(),
        ];
        $editingFishType = null;

        // Only fetch editing fish type if we're in edit mode
        if ($request->get('modal') === 'edit' && $request->has('edit')) {
            $editingFishType = FishType::whereHas('brokers', function ($query) use ($brokerId) {
                $query->where('brokers.id', $brokerId);
            })->find($request->get('edit'));
        }

        return compact('fishTypes', 'editingFishType', 'fishTypeSummary');
    }

    /**
     * Store a newly created fish type.
     *
     * @param FishTypeRequest $request
     *
     * @return RedirectResponse
     */
    public function store(FishTypeRequest $request): RedirectResponse
    {
        $userId = Auth::id();
        $brokerId = Broker::getBrokerIdByUserId($userId);

        $data = $request->validated();
        $fishType = FishType::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])->first();

        if ($fishType && $fishType->brokers()->where('brokers.id', $brokerId)->exists()) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->withInput()
                ->with('error', 'This fish type is already assigned to your account.');
        }

        if (!$fishType) {
            $fishType = FishType::create($data);
        } elseif (!empty($data['description']) && empty($fishType->description)) {
            $fishType->update(['description' => $data['description']]);
        }

        $fishType->brokers()->syncWithoutDetaching([$brokerId]);

        return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
            ->with('success', 'Fish type created successfully!');
    }

    /**
     * Update the specified fish type.
     *
     * @param FishTypeRequest $request
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function update(FishTypeRequest $request, $id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishType = FishType::whereHas('brokers', function ($query) use ($brokerId) {
            $query->where('brokers.id', $brokerId);
        })->findOrFail($id);

        $fishType->update($request->validated());

        return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
            ->with('success', 'Fish type updated successfully!');
    }

    /**
     * Remove the specified fish type.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function destroy($id): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $fishType = FishType::whereHas('brokers', function ($query) use ($brokerId) {
            $query->where('brokers.id', $brokerId);
        })->findOrFail($id);

        // Check if fish type has associated fish boxes
        if ($fishType->isUsed($brokerId)) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->with('error', 'Cannot delete fish type that has associated fish boxes.');
        }

        $fishType->brokers()->detach($brokerId);

        if ($fishType->brokers()->count() === 0 && !$fishType->fishBoxes()->exists()) {
            $fishType->delete();
        }

        return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
            ->with('success', 'Fish type deleted successfully!');
    }
}
