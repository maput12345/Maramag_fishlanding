<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\SalesStatusConstant;
use App\Http\Controllers\Broker\FishBoxController;
use App\Http\Controllers\Broker\FishTypesController;
use App\Http\Requests\FishBoxRequest;
use App\Http\Requests\FishTypeRequest;
use App\Models\Broker;
use App\Models\BrokerFishType;
use App\Models\Buyer;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishPrice;
use App\Models\FishType;
use App\Models\InventoryLog;
use App\Models\Role;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventorySummaryConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fish_box_status_summary_matches_expected_broker_counts(): void
    {
        [$broker, $otherBroker] = $this->createBrokerPair();

        FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'box-in-stock',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'box-sold',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'box-returned',
            'box_status' => FishBoxStatusConstant::RETURNED,
        ]);

        FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'box-missing',
            'box_status' => FishBoxStatusConstant::MISSING,
        ]);

        FishBox::create([
            'broker_id' => $otherBroker->id,
            'qr_code' => 'other-broker-box',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        $summary = FishBox::getStatusSummary($broker->id);

        $this->assertSame([
            'unassigned' => 0,
            'in_stock' => 1,
            'sold' => 1,
            'returned' => 1,
            'missing' => 1,
            'total' => 4,
        ], $summary);
    }

    public function test_create_empty_boxes_registers_unassigned_boxes_without_purchase_cycles(): void
    {
        [$broker] = $this->createBrokerPair();

        $createdBoxes = FishBox::createEmptyBoxes(2, $broker->id);
        $summary = FishBox::getStatusSummary($broker->id);

        $this->assertCount(2, $createdBoxes);
        $this->assertCount(2, collect($createdBoxes)->pluck('qr_code')->filter()->unique());
        $this->assertTrue(collect($createdBoxes)->every(fn (FishBox $fishBox): bool => $fishBox->status === FishBoxStatusConstant::UNASSIGNED));
        $this->assertTrue(collect($createdBoxes)->every(fn (FishBox $fishBox): bool => $fishBox->currentPurchase === null));
        $this->assertTrue(collect($createdBoxes)->every(fn (FishBox $fishBox): bool => !$fishBox->canBeEdited()));
        $this->assertSame(2, $summary['unassigned']);
        $this->assertSame(2, $summary['total']);
    }

    public function test_manual_fish_box_edit_rejects_direct_sold_status_changes(): void
    {
        [$broker] = $this->createBrokerPair();
        $user = User::findOrFail($broker->user_id);
        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);
        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $fishType = FishType::create([
            'name' => 'manual-status-bangus',
            'description' => 'Milkfish',
        ]);

        BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $fishType->id,
        ]);

        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'manual-status-box',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $this->actingAs($user);

        try {
            $this->makeFishBoxUpdateRequest($fishBox->id, [
                'fish_type_id' => $fishType->id,
                'cost_price' => 100,
                'status' => FishBoxStatusConstant::SOLD,
            ], $user);

            $this->fail('Direct Sold status changes should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $fishBox->fresh()->status);
    }

    public function test_broker_fish_type_edit_does_not_rename_shared_fish_for_other_brokers(): void
    {
        [$broker, $otherBroker] = $this->createBrokerPair();
        $user = User::findOrFail($broker->user_id);
        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);
        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $sharedFishType = FishType::create([
            'name' => 'shared-bangus',
            'description' => 'Shared fish',
        ]);

        BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $sharedFishType->id,
        ]);

        BrokerFishType::create([
            'broker_id' => $otherBroker->id,
            'fish_type_id' => $sharedFishType->id,
        ]);

        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'shared-fish-box',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        $purchase = FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $sharedFishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $controller = app(FishTypesController::class);

        $this->actingAs($user);

        $response = $controller->update(
            $this->makeFishTypeUpdateRequest($sharedFishType->id, [
                'name' => 'broker-one-bangus',
                'description' => 'Broker-specific fish',
            ], $user),
            $sharedFishType->id
        );

        $this->assertSame(route('broker.inventory.index', ['tab' => 'fishTypes']), $response->getTargetUrl());
        $this->assertSame(
            'This fish is already used in purchases or prices. Add the new fish instead to keep history accurate.',
            session('error')
        );

        $brokerAssignment = BrokerFishType::where('broker_id', $broker->id)
            ->where('fish_type_id', $sharedFishType->id)
            ->first();
        $otherBrokerAssignment = BrokerFishType::where('broker_id', $otherBroker->id)
            ->where('fish_type_id', $sharedFishType->id)
            ->first();

        $this->assertNull(FishType::where('name', 'broker-one-bangus')->first());
        $this->assertSame('shared-bangus', $sharedFishType->fresh()->name);
        $this->assertSame('shared-bangus', $brokerAssignment?->display_name);
        $this->assertSame('Shared fish', $brokerAssignment?->display_description);
        $this->assertSame('shared-bangus', $otherBrokerAssignment?->display_name);
        $this->assertTrue($sharedFishType->brokers()->where('Broker.id', $otherBroker->id)->exists());
        $this->assertTrue($sharedFishType->brokers()->where('Broker.id', $broker->id)->exists());
        $this->assertSame($sharedFishType->id, $purchase->fresh()->fish_type_id);
        $this->assertSame('shared-bangus', $fishBox->fresh()->fish_type_name);
    }

    public function test_inventory_log_summary_groups_statuses_for_the_given_day(): void
    {
        [$broker] = $this->createBrokerPair();

        $fishType = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $fishBoxOne = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'summary-box-1',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        $fishBoxTwo = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'summary-box-2',
            'box_status' => FishBoxStatusConstant::RETURNED,
        ]);

        $purchaseOne = FishBoxPurchase::create([
            'fish_box_id' => $fishBoxOne->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $purchaseTwo = FishBoxPurchase::create([
            'fish_box_id' => $fishBoxTwo->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 120,
        ]);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $purchaseOne->id,
            'created_by_user_id' => $broker->user_id,
            'status' => FishBoxStatusConstant::IN_STOCK,
            'created_at' => '2026-04-22 08:30:00',
            'updated_at' => '2026-04-22 08:30:00',
        ]);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $purchaseOne->id,
            'created_by_user_id' => $broker->user_id,
            'status' => FishBoxStatusConstant::SOLD,
            'created_at' => '2026-04-22 09:00:00',
            'updated_at' => '2026-04-22 09:00:00',
        ]);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $purchaseTwo->id,
            'created_by_user_id' => $broker->user_id,
            'status' => FishBoxStatusConstant::RETURNED,
            'created_at' => '2026-04-22 10:00:00',
            'updated_at' => '2026-04-22 10:00:00',
        ]);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $purchaseTwo->id,
            'created_by_user_id' => $broker->user_id,
            'status' => FishBoxStatusConstant::MISSING,
            'created_at' => '2026-04-22 11:00:00',
            'updated_at' => '2026-04-22 11:00:00',
        ]);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $purchaseTwo->id,
            'created_by_user_id' => $broker->user_id,
            'status' => FishBoxStatusConstant::SOLD,
            'created_at' => '2026-04-21 11:00:00',
            'updated_at' => '2026-04-21 11:00:00',
        ]);

        $summary = InventoryLog::getSummaryForDate('2026-04-22');

        $this->assertSame([
            'stocked' => 1,
            'sold' => 1,
            'returned' => 1,
            'missing' => 1,
        ], $summary);
    }

    public function test_admin_tracking_uses_current_fish_box_status_while_history_keeps_old_events(): void
    {
        [$broker] = $this->createBrokerPair();

        $fishType = FishType::create([
            'name' => 'tilapia',
            'description' => 'Tilapia',
        ]);

        $returnedBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'tracking-box-returned',
            'box_status' => FishBoxStatusConstant::RETURNED,
        ]);

        $missingBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'tracking-box-missing',
            'box_status' => FishBoxStatusConstant::MISSING,
        ]);

        $returnedPurchase = FishBoxPurchase::create([
            'fish_box_id' => $returnedBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $missingPurchase = FishBoxPurchase::create([
            'fish_box_id' => $missingBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 120,
        ]);

        InventoryLog::query()->insert([
            [
                'fish_box_purchase_id' => $returnedPurchase->id,
                'created_by_user_id' => $broker->user_id,
                'status' => FishBoxStatusConstant::MISSING,
                'created_at' => '2026-04-21 09:00:00',
                'updated_at' => '2026-04-21 09:00:00',
            ],
            [
                'fish_box_purchase_id' => $returnedPurchase->id,
                'created_by_user_id' => $broker->user_id,
                'status' => FishBoxStatusConstant::RETURNED,
                'created_at' => '2026-04-22 09:30:00',
                'updated_at' => '2026-04-22 09:30:00',
            ],
            [
                'fish_box_purchase_id' => $missingPurchase->id,
                'created_by_user_id' => $broker->user_id,
                'status' => FishBoxStatusConstant::MISSING,
                'created_at' => '2026-04-22 10:00:00',
                'updated_at' => '2026-04-22 10:00:00',
            ],
        ]);

        $trackedBoxes = FishBox::getAdminTrackingStatuses(null, null, null, 20)->getCollection();
        $dashboardMissingBoxes = FishBox::getCurrentMissingBoxes(10);
        $inventoryLogs = InventoryLog::getPaginatedWithFilters(null, null, null, 20)->getCollection();

        $this->assertCount(2, $trackedBoxes);
        $this->assertSame(FishBoxStatusConstant::RETURNED, $trackedBoxes->firstWhere('id', $returnedBox->id)?->status);
        $this->assertSame(FishBoxStatusConstant::MISSING, $trackedBoxes->firstWhere('id', $missingBox->id)?->status);
        $this->assertTrue($dashboardMissingBoxes->pluck('id')->contains($missingBox->id));
        $this->assertFalse($dashboardMissingBoxes->pluck('id')->contains($returnedBox->id));
        $this->assertSame('Broker One', $dashboardMissingBoxes->firstWhere('id', $missingBox->id)?->broker?->name);
        $this->assertSame('Stall 1', $inventoryLogs->firstWhere('fish_box_purchase_id', $missingPurchase->id)?->broker?->stall_name);
    }

    public function test_broker_fish_box_helpers_keep_status_and_purchase_data_when_box_number_is_selected(): void
    {
        [$broker] = $this->createBrokerPair();

        $fishType = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $inStockBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'inventory-box-in-stock',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $inStockBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $missingBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'inventory-box-missing',
            'box_status' => FishBoxStatusConstant::MISSING,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $missingBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 120,
        ]);

        $editingFishBox = FishBox::getFishBoxByIdAndBroker($missingBox->id, $broker->id);
        $qrFishBox = FishBox::getFishBoxByQrCode($missingBox->qr_code, $broker->id);
        $availableFishBoxes = FishBox::getAvailableForSale($broker->id);

        $this->assertSame(FishBoxStatusConstant::MISSING, $editingFishBox->status);
        $this->assertSame($fishType->id, $editingFishBox->fish_type_id);
        $this->assertSame('120.00', $editingFishBox->cost_price);
        $this->assertSame(FishBoxStatusConstant::MISSING, $qrFishBox?->status);
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $availableFishBoxes->firstWhere('id', $inStockBox->id)?->status);
    }

    public function test_broker_bulk_qr_print_dataset_respects_current_filters(): void
    {
        [$broker, $otherBroker] = $this->createBrokerPair();

        $bangus = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $tuna = FishType::create([
            'name' => 'tuna',
            'description' => 'Tuna',
        ]);

        $matchingBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'bulk-qr-match',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $matchingBox->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $soldBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'bulk-qr-sold',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $soldBox->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 110,
        ]);

        $otherTypeBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'bulk-qr-tuna',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $otherTypeBox->id,
            'fish_type_id' => $tuna->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 115,
        ]);

        $otherBrokerBox = FishBox::create([
            'broker_id' => $otherBroker->id,
            'qr_code' => 'bulk-qr-other-broker',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $otherBrokerBox->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $otherBroker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 125,
        ]);

        $bulkQrBoxes = FishBox::getFilteredForBulkQrPrint(null, FishBoxStatusConstant::IN_STOCK, $bangus->id, $broker->id);

        $this->assertCount(1, $bulkQrBoxes);
        $this->assertSame($matchingBox->id, $bulkQrBoxes->first()['id']);
        $this->assertSame('Fish Box #01', $bulkQrBoxes->first()['name']);
        $this->assertSame('bangus', $bulkQrBoxes->first()['fish_name']);
        $this->assertSame('bulk-qr-match', $bulkQrBoxes->first()['qr_code']);
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $bulkQrBoxes->first()['status']);
    }

    public function test_cutoff_command_marks_only_due_sold_boxes_as_missing(): void
    {
        [$broker] = $this->createBrokerPair();

        $fishType = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $dueSoldBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'cutoff-due-box',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        $notYetDueSoldBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'cutoff-future-box',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        $returnedBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'cutoff-returned-box',
            'box_status' => FishBoxStatusConstant::RETURNED,
        ]);

        $duePurchase = FishBoxPurchase::create([
            'fish_box_id' => $dueSoldBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        $futurePurchase = FishBoxPurchase::create([
            'fish_box_id' => $notYetDueSoldBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 120,
        ]);

        $returnedPurchase = FishBoxPurchase::create([
            'fish_box_id' => $returnedBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 130,
        ]);

        InventoryLog::query()->insert([
            [
                'fish_box_purchase_id' => $duePurchase->id,
                'created_by_user_id' => $broker->user_id,
                'status' => FishBoxStatusConstant::SOLD,
                'created_at' => '2026-04-22 10:30:00',
                'updated_at' => '2026-04-22 10:30:00',
            ],
            [
                'fish_box_purchase_id' => $futurePurchase->id,
                'created_by_user_id' => $broker->user_id,
                'status' => FishBoxStatusConstant::SOLD,
                'created_at' => '2026-04-22 12:05:00',
                'updated_at' => '2026-04-22 12:05:00',
            ],
            [
                'fish_box_purchase_id' => $returnedPurchase->id,
                'created_by_user_id' => $broker->user_id,
                'status' => FishBoxStatusConstant::RETURNED,
                'created_at' => '2026-04-22 10:00:00',
                'updated_at' => '2026-04-22 10:00:00',
            ],
        ]);

        FishBox::withoutTimestamps(function () use ($dueSoldBox, $notYetDueSoldBox, $returnedBox) {
            $dueSoldBox->forceFill(['updated_at' => '2026-04-22 10:30:00'])->save();
            $notYetDueSoldBox->forceFill(['updated_at' => '2026-04-22 12:05:00'])->save();
            $returnedBox->forceFill(['updated_at' => '2026-04-22 10:00:00'])->save();
        });

        Artisan::call('fish-boxes:mark-sold-missing', [
            '--cutoff' => '2026-04-22 11:59:00',
        ]);

        $dueSoldBox->refresh();
        $notYetDueSoldBox->refresh();
        $returnedBox->refresh();

        $this->assertSame(FishBoxStatusConstant::MISSING, $dueSoldBox->status);
        $this->assertSame(FishBoxStatusConstant::SOLD, $notYetDueSoldBox->status);
        $this->assertSame(FishBoxStatusConstant::RETURNED, $returnedBox->status);

        $this->assertTrue($dueSoldBox->inventoryLogs()->where('status', FishBoxStatusConstant::MISSING)->exists());
        $this->assertFalse($notYetDueSoldBox->inventoryLogs()->where('status', FishBoxStatusConstant::MISSING)->exists());
    }

    public function test_cutoff_command_does_not_mark_boxes_before_1159_am(): void
    {
        [$broker] = $this->createBrokerPair();

        $fishType = FishType::create([
            'name' => 'tamban',
            'description' => 'Sardine',
        ]);

        $soldBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'before-cutoff-sold-box',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $soldBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-23',
            'cost_price' => 95,
        ]);

        FishBox::withoutTimestamps(function () use ($soldBox) {
            $soldBox->forceFill(['updated_at' => '2026-04-23 09:30:00'])->save();
        });

        Carbon::setTestNow('2026-04-23 10:00:00');

        try {
            Artisan::call('fish-boxes:mark-sold-missing');
        } finally {
            Carbon::setTestNow();
        }

        $soldBox->refresh();

        $this->assertSame(FishBoxStatusConstant::SOLD, $soldBox->status);
        $this->assertFalse($soldBox->inventoryLogs()->where('status', FishBoxStatusConstant::MISSING)->exists());
    }

    public function test_broker_missing_tracking_only_shows_current_missing_boxes_with_last_buyer(): void
    {
        [$broker, $otherBroker] = $this->createBrokerPair();

        $fishType = FishType::create([
            'name' => 'tuna',
            'description' => 'Tuna',
        ]);

        $missingBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'broker-tracking-missing',
            'box_status' => FishBoxStatusConstant::MISSING,
        ]);

        $returnedBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'broker-tracking-returned',
            'box_status' => FishBoxStatusConstant::RETURNED,
        ]);

        $otherBrokerMissingBox = FishBox::create([
            'broker_id' => $otherBroker->id,
            'qr_code' => 'other-broker-missing',
            'box_status' => FishBoxStatusConstant::MISSING,
        ]);

        $missingPurchase = FishBoxPurchase::create([
            'fish_box_id' => $missingBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 150,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $returnedBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 155,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $otherBrokerMissingBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $otherBroker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 160,
        ]);

        $buyer = Buyer::create([
            'first_name' => 'Liza',
            'middle_name' => null,
            'last_name' => 'Buyer',
            'contact' => '09170000011',
        ]);

        $sale = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $broker->id,
            'buyer_id' => $buyer->id,
            'total_amount' => 150,
            'status' => SalesStatusConstant::ACTIVE,
        ]);

        SalesDetails::create([
            'sale_id' => $sale->id,
            'fish_box_purchase_id' => $missingPurchase->id,
            'unit_price' => 150,
            'sub_total' => 150,
            'discount' => 0,
        ]);

        $trackingBoxes = FishBox::getBrokerMissingTracking($broker->id, null, 20)->getCollection();

        $this->assertCount(1, $trackingBoxes);
        $this->assertSame($missingBox->id, $trackingBoxes->first()?->id);
        $this->assertSame('Fish Box #01', $trackingBoxes->first()?->name);
        $this->assertSame('tuna', $trackingBoxes->first()?->fish_type_name);
        $this->assertSame('Liza Buyer', $trackingBoxes->first()?->last_buyer_name);
        $this->assertFalse($trackingBoxes->pluck('id')->contains($returnedBox->id));
        $this->assertFalse($trackingBoxes->pluck('id')->contains($otherBrokerMissingBox->id));
    }

    public function test_default_cost_lookup_uses_the_latest_broker_fish_price(): void
    {
        [$broker, $otherBroker] = $this->createBrokerPair();

        $bangus = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $tuna = FishType::create([
            'name' => 'tuna',
            'description' => 'Tuna',
        ]);

        $brokerBangusAssignment = BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $bangus->id,
        ]);

        BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $tuna->id,
        ]);

        $otherBrokerBangusAssignment = BrokerFishType::create([
            'broker_id' => $otherBroker->id,
            'fish_type_id' => $bangus->id,
        ]);

        FishPrice::create([
            'broker_fish_type_id' => $brokerBangusAssignment->id,
            'price' => 180,
            'default_cost_price' => 120,
            'price_date' => '2026-04-22',
        ]);

        FishPrice::create([
            'broker_fish_type_id' => $brokerBangusAssignment->id,
            'price' => 185,
            'default_cost_price' => 145,
            'price_date' => '2026-04-23',
        ]);

        FishPrice::create([
            'broker_fish_type_id' => $otherBrokerBangusAssignment->id,
            'price' => 190,
            'default_cost_price' => 175,
            'price_date' => '2026-04-23',
        ]);

        $defaultCostMap = FishBox::getDefaultCostMapForBroker($broker->id);

        $this->assertSame('145.00', $defaultCostMap[(string) $bangus->id]);
        $this->assertArrayNotHasKey((string) $tuna->id, $defaultCostMap);
        $this->assertSame(145.0, FishBox::getDefaultCostPriceForBrokerFishType($broker->id, $bangus->id));
        $this->assertNull(FishBox::getDefaultCostPriceForBrokerFishType($broker->id, $tuna->id));
    }

    public function test_bulk_restock_creates_new_purchase_cycles_without_overwriting_previous_sales_history(): void
    {
        [$broker] = $this->createBrokerPair();

        $budlisan = FishType::create([
            'name' => 'budlisan',
            'description' => 'Threadfin bream',
        ]);

        $tuna = FishType::create([
            'name' => 'tuna',
            'description' => 'Tuna',
        ]);

        $returnedBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'restock-returned-box',
            'box_status' => FishBoxStatusConstant::RETURNED,
        ]);

        $unassignedBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'restock-unassigned-box',
            'box_status' => FishBoxStatusConstant::UNASSIGNED,
        ]);

        $inStockBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'restock-stock-box',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        $soldBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'restock-sold-box',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        $returnedPurchase = FishBoxPurchase::create([
            'fish_box_id' => $returnedBox->id,
            'fish_type_id' => $budlisan->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 3000,
        ]);

        $inStockPurchase = FishBoxPurchase::create([
            'fish_box_id' => $inStockBox->id,
            'fish_type_id' => $budlisan->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 3200,
        ]);

        $soldPurchase = FishBoxPurchase::create([
            'fish_box_id' => $soldBox->id,
            'fish_type_id' => $budlisan->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 3400,
        ]);

        $buyer = Buyer::create([
            'first_name' => 'Paolo',
            'middle_name' => null,
            'last_name' => 'Buyer',
            'contact' => '09170000013',
        ]);

        $sale = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $broker->id,
            'buyer_id' => $buyer->id,
            'total_amount' => 3000,
            'status' => SalesStatusConstant::ACTIVE,
        ]);

        $saleDetail = SalesDetails::create([
            'sale_id' => $sale->id,
            'fish_box_purchase_id' => $returnedPurchase->id,
            'unit_price' => 3000,
            'sub_total' => 3000,
            'discount' => 0,
        ]);

        $restockedCount = FishBox::bulkRestock(
            $broker->id,
            [$returnedBox->id, $unassignedBox->id, $inStockBox->id, $soldBox->id],
            $tuna->id,
            5000,
            $broker->user_id
        );

        $returnedBox->refresh()->load('currentPurchase.fishType');
        $unassignedBox->refresh()->load('currentPurchase.fishType');
        $inStockBox->refresh()->load('currentPurchase.fishType');
        $soldBox->refresh()->load('currentPurchase.fishType');

        $this->assertSame(3, $restockedCount);
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $returnedBox->status);
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $unassignedBox->status);
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $inStockBox->status);
        $this->assertSame(FishBoxStatusConstant::SOLD, $soldBox->status);

        $this->assertNotNull($unassignedBox->currentPurchase);
        $this->assertNotSame($returnedPurchase->id, $returnedBox->currentPurchase?->id);
        $this->assertNotSame($inStockPurchase->id, $inStockBox->currentPurchase?->id);
        $this->assertSame($soldPurchase->id, $soldBox->currentPurchase?->id);

        $this->assertSame($tuna->id, $returnedBox->currentPurchase?->fish_type_id);
        $this->assertSame($tuna->id, $unassignedBox->currentPurchase?->fish_type_id);
        $this->assertSame($tuna->id, $inStockBox->currentPurchase?->fish_type_id);
        $this->assertSame('5000.00', $returnedBox->currentPurchase?->cost_price);
        $this->assertSame('5000.00', $unassignedBox->currentPurchase?->cost_price);
        $this->assertSame('5000.00', $inStockBox->currentPurchase?->cost_price);

        $this->assertSame($returnedPurchase->id, $saleDetail->fresh()->fish_box_purchase_id);
        $this->assertSame($budlisan->id, $returnedPurchase->fresh()->fish_type_id);
        $this->assertTrue($returnedBox->inventoryLogs()->where('status', FishBoxStatusConstant::IN_STOCK)->count() >= 1);
    }

    public function test_manual_edit_rejects_changes_that_would_overwrite_sales_linked_purchase_history(): void
    {
        [$broker] = $this->createBrokerPair();
        $user = User::findOrFail($broker->user_id);
        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);
        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $bangus = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $tuna = FishType::create([
            'name' => 'tuna',
            'description' => 'Tuna',
        ]);

        BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $bangus->id,
        ]);

        BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $tuna->id,
        ]);

        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'history-lock-box',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        $purchase = FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 3000,
        ]);

        $buyer = Buyer::create([
            'first_name' => 'Locked',
            'middle_name' => null,
            'last_name' => 'Buyer',
            'contact' => '09170000014',
        ]);

        $sale = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $broker->id,
            'buyer_id' => $buyer->id,
            'total_amount' => 3000,
            'status' => SalesStatusConstant::ACTIVE,
        ]);

        SalesDetails::create([
            'sale_id' => $sale->id,
            'fish_box_purchase_id' => $purchase->id,
            'unit_price' => 3000,
            'sub_total' => 3000,
            'discount' => 0,
        ]);

        $controller = app(FishBoxController::class);

        $this->actingAs($user);

        $response = $controller->update(
            $this->makeFishBoxUpdateRequest($fishBox->id, [
                'fish_type_id' => $tuna->id,
                'cost_price' => 5000,
                'status' => FishBoxStatusConstant::IN_STOCK,
            ], $user),
            $fishBox->id
        );

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);

        $fishBox->refresh()->load('currentPurchase');
        $purchase->refresh();

        $this->assertSame($bangus->id, $fishBox->currentPurchase?->fish_type_id);
        $this->assertSame('3000.00', $fishBox->currentPurchase?->cost_price);
        $this->assertSame($purchase->id, $fishBox->currentPurchase?->id);
    }

    public function test_qr_return_only_allows_sold_or_missing_boxes(): void
    {
        [$broker] = $this->createBrokerPair();
        $user = User::findOrFail($broker->user_id);
        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);
        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $fishType = FishType::create([
            'name' => 'galunggong',
            'description' => 'Round scad',
        ]);

        $soldBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'qr-return-sold',
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        $missingBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'qr-return-missing',
            'box_status' => FishBoxStatusConstant::MISSING,
        ]);

        $inStockBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'qr-return-stock',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $soldBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 100,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $missingBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 110,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $inStockBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $broker->user_id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 120,
        ]);

        $this->assertSame($broker->id, Broker::getBrokerIdByUserId($user->id));
        $this->assertNotNull(FishBox::getFishBoxByQrCode($soldBox->qr_code, $broker->id));
        $this->assertNotNull(FishBox::getFishBoxByQrCode($missingBox->qr_code, $broker->id));
        $this->assertNotNull(FishBox::getFishBoxByQrCode($inStockBox->qr_code, $broker->id));

        $controller = app(FishBoxController::class);

        $this->actingAs($user);

        $soldResponse = $controller->returnFishBoxViaQr(
            Request::create('/broker/fish-boxes/return-via-qr', 'POST', ['qr_code' => $soldBox->qr_code])
        );
        $this->assertSame(200, $soldResponse->getStatusCode());

        $missingResponse = $controller->returnFishBoxViaQr(
            Request::create('/broker/fish-boxes/return-via-qr', 'POST', ['qr_code' => $missingBox->qr_code])
        );
        $this->assertSame(200, $missingResponse->getStatusCode());

        $inStockResponse = $controller->returnFishBoxViaQr(
            Request::create('/broker/fish-boxes/return-via-qr', 'POST', ['qr_code' => $inStockBox->qr_code])
        );
        $this->assertSame(400, $inStockResponse->getStatusCode());
        $this->assertSame([
                'success' => false,
                'message' => 'Only sold or missing fish boxes can be returned.',
            ], $inStockResponse->getData(true));

        $this->assertSame(FishBoxStatusConstant::RETURNED, $soldBox->fresh()->status);
        $this->assertSame(FishBoxStatusConstant::RETURNED, $missingBox->fresh()->status);
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $inStockBox->fresh()->status);
    }

    /**
     * @return array{0: Broker, 1: Broker}
     */
    private function createBrokerPair(): array
    {
        $userOne = User::create([
            'email' => 'broker-one@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $userTwo = User::create([
            'email' => 'broker-two@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $brokerOne = Broker::create([
            'user_id' => $userOne->id,
            'first_name' => 'Broker',
            'middle_name' => null,
            'last_name' => 'One',
            'address' => 'Market 1',
            'stall_name' => 'Stall 1',
            'broker_status' => 'Active',
        ]);

        $brokerTwo = Broker::create([
            'user_id' => $userTwo->id,
            'first_name' => 'Broker',
            'middle_name' => null,
            'last_name' => 'Two',
            'address' => 'Market 2',
            'stall_name' => 'Stall 2',
            'broker_status' => 'Active',
        ]);

        return [$brokerOne, $brokerTwo];
    }

    private function makeFishBoxUpdateRequest(int $fishBoxId, array $payload, User $user): FishBoxRequest
    {
        $request = FishBoxRequest::create(
            '/broker/fish-boxes/' . $fishBoxId,
            'PUT',
            $payload,
            [],
            [],
            [
                'HTTP_REFERER' => route('broker.inventory.index', [
                    'tab' => 'fishBoxes',
                    'modal' => 'edit',
                    'edit' => $fishBoxId,
                ]),
            ]
        );
        $router = $this->app->make('router');
        $route = $router->getRoutes()->match($request);

        $router->substituteBindings($route);
        $router->substituteImplicitBindings($route);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);
        $request->validateResolved();

        return $request;
    }

    private function makeFishTypeUpdateRequest(int $fishTypeId, array $payload, User $user): FishTypeRequest
    {
        $request = FishTypeRequest::create(
            '/broker/fish-types/' . $fishTypeId,
            'PUT',
            $payload,
            [],
            [],
            [
                'HTTP_REFERER' => route('broker.inventory.index', [
                    'tab' => 'fishTypes',
                    'modal' => 'edit',
                    'edit' => $fishTypeId,
                ]),
            ]
        );
        $router = $this->app->make('router');
        $route = $router->getRoutes()->match($request);

        $router->substituteBindings($route);
        $router->substituteImplicitBindings($route);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);
        $request->validateResolved();

        return $request;
    }
}
