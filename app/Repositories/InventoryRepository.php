<?php

namespace App\Repositories;

use App\Models\FishBox;
use App\Constants\FishBoxStatusConstant;

class InventoryRepository
{
    /**
     * Get inventory analysis data
     */
    public function getInventoryAnalysisData(): array
    {
        $stockStatus = FishBox::getStatusSummary();
        $stockStatus = [
            FishBoxStatusConstant::IN_STOCK => $stockStatus['in_stock'],
            FishBoxStatusConstant::SOLD => $stockStatus['sold'],
            FishBoxStatusConstant::RETURNED => $stockStatus['returned'],
            FishBoxStatusConstant::MISSING => $stockStatus['missing'],
        ];

        $totalFishBoxes = array_sum($stockStatus);
        $inStockCount = $stockStatus[FishBoxStatusConstant::IN_STOCK];
        $soldCount = $stockStatus[FishBoxStatusConstant::SOLD];
        $totalInventory = $inStockCount + $soldCount;
        $turnoverRate = $totalInventory > 0 ? ($soldCount / $totalInventory) * 100 : 0;

        // Calculate percentages for each status
        $stockStatusWithPercentages = [];
        foreach ($stockStatus as $statusName => $count) {
            $percentage = $totalFishBoxes > 0 ? ($count / $totalFishBoxes) * 100 : 0;
            $colorClass = $statusName === FishBoxStatusConstant::IN_STOCK ? 'bg-green-500' :
                         ($statusName === FishBoxStatusConstant::SOLD ? 'bg-blue-500' :
                         ($statusName === FishBoxStatusConstant::RETURNED ? 'bg-yellow-500' : 'bg-red-500'));
            $bgClass = $statusName === FishBoxStatusConstant::IN_STOCK ? 'bg-green-50' :
                      ($statusName === FishBoxStatusConstant::SOLD ? 'bg-blue-50' :
                      ($statusName === FishBoxStatusConstant::RETURNED ? 'bg-yellow-50' : 'bg-red-50'));

            $stockStatusWithPercentages[$statusName] = [
                'count' => $count,
                'percentage' => $percentage,
                'color_class' => $colorClass,
                'bg_class' => $bgClass
            ];
        }

        return [
            'stock_status' => $stockStatusWithPercentages,
            'total_fish_boxes' => $totalFishBoxes,
            'in_stock_count' => $inStockCount,
            'sold_count' => $soldCount,
            'total_inventory' => $totalInventory,
            'turnover_rate' => $turnoverRate
        ];
    }
}
