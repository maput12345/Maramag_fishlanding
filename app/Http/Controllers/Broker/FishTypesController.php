<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\FishTypeRequest;
use App\Models\BrokerFishTypeAssignment;
use App\Models\FishType;
use App\Models\Broker;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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
            'assigned' => BrokerFishTypeAssignment::where('broker_id', $brokerId)->count(),
            'with_prices' => BrokerFishTypeAssignment::where('broker_id', $brokerId)->whereHas('latestPrice')->count(),
            'used' => BrokerFishTypeAssignment::where('broker_id', $brokerId)
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
            $editingFishType = FishType::query()
                ->select([
                    'FishType.*',
                    'BrokerFishTypeAssignment.display_name as broker_display_name',
                    'BrokerFishTypeAssignment.display_description as broker_display_description',
                ])
                ->join('BrokerFishTypeAssignment', 'BrokerFishTypeAssignment.fish_type_id', '=', 'FishType.id')
                ->where('BrokerFishTypeAssignment.broker_id', $brokerId)
                ->where('FishType.id', $request->get('edit'))
                ->first();
        }

        return compact('fishTypes', 'editingFishType', 'fishTypeSummary', 'brokerId');
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
        $displayName = trim($data['name']);
        $displayDescription = $this->normalizeNullableText($data['description'] ?? null);
        $normalizedName = mb_strtolower($displayName);

        if ($this->brokerHasDisplayName($brokerId, $normalizedName)) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->withInput()
                ->with('error', 'This fish type is already assigned to your account.');
        }

        $fishType = FishType::whereRaw('LOWER(name) = ?', [$normalizedName])->first();

        if ($fishType && $this->brokerHasFishType($brokerId, $fishType->id)) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->withInput()
                ->with('error', 'This fish type is already assigned to your account.');
        }

        if (!$fishType) {
            $fishType = FishType::create([
                'name' => $displayName,
                'description' => $displayDescription,
            ]);
        }

        BrokerFishTypeAssignment::create([
            'broker_id' => $brokerId,
            'fish_type_id' => $fishType->id,
            'display_name' => $displayName,
            'display_description' => $displayDescription,
        ]);

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
        $assignment = BrokerFishTypeAssignment::query()
            ->withCount(['prices'])
            ->where('broker_id', $brokerId)
            ->where('fish_type_id', $id)
            ->firstOrFail();
        $data = $request->validated();
        $fishName = trim($data['name']);
        $description = $this->normalizeNullableText($data['description'] ?? null);
        $normalizedName = mb_strtolower($fishName);

        if ($this->brokerHasDisplayName($brokerId, $normalizedName, $assignment->id)) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->withInput()
                ->with('error', 'This fish type is already assigned to your account.');
        }

        $currentFishType = FishType::findOrFail($id);
        $isChangingFishType = mb_strtolower($currentFishType->name) !== $normalizedName;
        $isAssignmentLocked = $currentFishType->isUsed($brokerId) || (int) $assignment->prices_count > 0;

        if ($isChangingFishType && $isAssignmentLocked) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->withInput()
                ->with('error', 'This fish already has purchases or prices. Add the new fish instead so old history stays accurate.');
        }

        $targetFishType = $currentFishType;

        if ($isChangingFishType && !$isAssignmentLocked) {
            $targetFishType = FishType::whereRaw('LOWER(name) = ?', [$normalizedName])->first();

            if (!$targetFishType) {
                $targetFishType = FishType::create([
                    'name' => $fishName,
                    'description' => $description,
                ]);
            }
        }

        if ($this->brokerHasFishType($brokerId, $targetFishType->id, $assignment->id)) {
            return redirect()->route('broker.inventory.index', ['tab' => 'fishTypes'])
                ->withInput()
                ->with('error', 'This fish type is already assigned to your account.');
        }

        $assignment->update([
            'fish_type_id' => $targetFishType->id,
            'display_name' => $fishName,
            'display_description' => $description,
        ]);

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
            $query->where('Broker.id', $brokerId);
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

    private function normalizeNullableText(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function brokerHasDisplayName(int $brokerId, string $normalizedName, ?int $exceptAssignmentId = null): bool
    {
        return BrokerFishTypeAssignment::query()
            ->join('FishType', 'FishType.id', '=', 'BrokerFishTypeAssignment.fish_type_id')
            ->where('BrokerFishTypeAssignment.broker_id', $brokerId)
            ->when($exceptAssignmentId, function ($query) use ($exceptAssignmentId) {
                $query->where('BrokerFishTypeAssignment.id', '!=', $exceptAssignmentId);
            })
            ->whereRaw('LOWER(COALESCE(BrokerFishTypeAssignment.display_name, FishType.name)) = ?', [$normalizedName])
            ->exists();
    }

    private function brokerHasFishType(int $brokerId, int $fishTypeId, ?int $exceptAssignmentId = null): bool
    {
        return BrokerFishTypeAssignment::query()
            ->where('broker_id', $brokerId)
            ->where('fish_type_id', $fishTypeId)
            ->when($exceptAssignmentId, function ($query) use ($exceptAssignmentId) {
                $query->where('id', '!=', $exceptAssignmentId);
            })
            ->exists();
    }
}
