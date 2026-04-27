<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\SalesStatusConstant;
use App\Models\Broker;
use App\Models\Buyer;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishType;
use App\Models\InventoryLog;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\SalesPayment;
use App\Models\User;
use App\Repositories\SalesRepository;
use App\Http\Controllers\Broker\SalesController;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SalesMetricsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-22 09:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sales_metrics_and_paginated_totals_stay_consistent(): void
    {
        $dataset = $this->seedSalesDataset();
        $brokerId = $dataset['brokers']['main']->id;

        $summary = Sales::getSummaryForFilters(null, null, $brokerId, '2026-04-21', '2026-04-22');

        $this->assertSame(3, $summary['count']);
        $this->assertEqualsWithDelta(230.0, $summary['gross_total'], 0.01);
        $this->assertEqualsWithDelta(140.0, $summary['paid_total'], 0.01);
        $this->assertEqualsWithDelta(90.0, $summary['balance_total'], 0.01);

        $this->assertEqualsWithDelta(120.0, Sales::getTotalPaidAmountToday($brokerId), 0.01);
        $this->assertEqualsWithDelta(20.0, Sales::getTotalPaidAmountYesterday($brokerId), 0.01);
        $this->assertEqualsWithDelta(90.0, Sales::getTotalSalesBalance($brokerId), 0.01);

        $sales = Sales::getPaginatedWithFilters(null, null, $brokerId, '2026-04-21', '2026-04-22');
        $saleOne = $sales->getCollection()->firstWhere('id', $dataset['sales']['sale_one']->id);
        $saleThree = $sales->getCollection()->firstWhere('id', $dataset['sales']['sale_three']->id);

        $this->assertNotNull($saleOne);
        $this->assertNotNull($saleThree);
        $this->assertEqualsWithDelta(40.0, $saleOne->paid_amount, 0.01);
        $this->assertEqualsWithDelta(60.0, $saleOne->remaining_amount, 0.01);
        $this->assertEqualsWithDelta(20.0, $saleThree->paid_amount, 0.01);
        $this->assertEqualsWithDelta(30.0, $saleThree->remaining_amount, 0.01);
        $this->assertSame('bangus', $saleOne->formatted_items);
    }

    public function test_analytics_payments_and_stock_counts_match_expected_results(): void
    {
        $dataset = $this->seedSalesDataset();
        $brokerId = $dataset['brokers']['main']->id;

        $analytics = Sales::getAnalyticsData($brokerId, '2026-04-21', '2026-04-22');

        $this->assertEqualsWithDelta(140.0, $analytics['totalRevenue'], 0.01);
        $this->assertSame(3, $analytics['totalOrders']);
        $this->assertEqualsWithDelta(90.0, $analytics['totalBalance'], 0.01);
        $this->assertSame(4, $analytics['totalFishBoxes']);

        $topBangus = collect($analytics['topItems'])->firstWhere('name', 'bangus');
        $topTuna = collect($analytics['topItems'])->firstWhere('name', 'tuna');

        $this->assertNotNull($topBangus);
        $this->assertNotNull($topTuna);
        $this->assertSame(3, $topBangus['quantity']);
        $this->assertEqualsWithDelta(150.0, $topBangus['revenue'], 0.01);
        $this->assertSame(1, $topTuna['quantity']);
        $this->assertEqualsWithDelta(80.0, $topTuna['revenue'], 0.01);

        $paymentMethods = SalesPayment::getPaymentMethodsBreakdown($brokerId, '2026-04-21', '2026-04-22');
        $cash = $paymentMethods->firstWhere('name', 'Cash');
        $gcash = $paymentMethods->firstWhere('name', 'GCash');

        $this->assertNotNull($cash);
        $this->assertNotNull($gcash);
        $this->assertSame(2, $cash['transactions']);
        $this->assertEqualsWithDelta(60.0, $cash['amount'], 0.01);
        $this->assertSame(1, $gcash['transactions']);
        $this->assertEqualsWithDelta(80.0, $gcash['amount'], 0.01);

        $repository = new SalesRepository();
        $topBrokers = $repository->getTopBrokersWithFishBoxCount();
        $mainBrokerRow = $topBrokers->firstWhere('broker.id', $brokerId);

        $this->assertNotNull($mainBrokerRow);
        $this->assertSame(3, $mainBrokerRow['sales_count']);
        $this->assertEqualsWithDelta(140.0, $mainBrokerRow['total_sales'], 0.01);
        $this->assertSame(4, $mainBrokerRow['fishbox_count']);
        $this->assertSame(4, $repository->getTotalFishBoxesSold('2026-04-21', '2026-04-22', 'Main Stall'));
    }

    public function test_analytics_controller_keeps_historical_sold_box_count_even_after_boxes_are_returned(): void
    {
        $dataset = $this->seedSalesDataset();
        $broker = $dataset['brokers']['main'];
        $user = $broker->user;
        $returnedBoxIds = SalesDetails::query()
            ->join('sales', 'sales.id', '=', 'sales_details.sale_id')
            ->join('fish_box_purchases', 'fish_box_purchases.id', '=', 'sales_details.fish_box_purchase_id')
            ->where('sales.broker_id', $broker->id)
            ->whereIn('sales.status', SalesStatusConstant::getAllActiveStatuses())
            ->pluck('fish_box_purchases.fish_box_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        FishBox::query()
            ->where('broker_id', $broker->id)
            ->whereIn('id', $returnedBoxIds)
            ->update(['box_status' => FishBoxStatusConstant::RETURNED]);

        $this->actingAs($user);

        $controller = new SalesController();
        $analytics = $controller->getAnalyticsData(new Request([
            'date_from' => '2026-04-21',
            'date_to' => '2026-04-22',
        ]));

        $this->assertSame(4, $analytics['totalFishBoxes']);
    }

    public function test_admin_broker_sales_details_can_render_without_lazy_loading(): void
    {
        $dataset = $this->seedSalesDataset();
        $repository = new SalesRepository();
        $mainBroker = $dataset['brokers']['main'];
        $mainUserId = $mainBroker->user_id;
        $fishTypeId = FishType::query()->where('name', 'bangus')->value('id');

        $carryOverMissingPurchase = $this->createFishBoxWithPurchase($mainBroker->id, $fishTypeId, 55.00, $mainUserId);
        $carryOverMissingBox = $carryOverMissingPurchase->fishBox;
        $carryOverMissingBox->update(['box_status' => FishBoxStatusConstant::MISSING]);
        InventoryLog::query()
            ->where('fish_box_purchase_id', $carryOverMissingPurchase->id)
            ->update([
                'created_at' => '2026-04-20 08:00:00',
                'updated_at' => '2026-04-20 08:00:00',
            ]);
        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $carryOverMissingPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::MISSING,
            'created_at' => '2026-04-21 10:15:00',
            'updated_at' => '2026-04-21 10:15:00',
        ]);

        $returnedBeforeReceiptPurchase = $this->createFishBoxWithPurchase($mainBroker->id, $fishTypeId, 60.00, $mainUserId);
        $returnedBeforeReceiptBox = $returnedBeforeReceiptPurchase->fishBox;
        $returnedBeforeReceiptBox->update(['box_status' => FishBoxStatusConstant::RETURNED]);
        InventoryLog::query()
            ->where('fish_box_purchase_id', $returnedBeforeReceiptPurchase->id)
            ->update([
                'created_at' => '2026-04-20 08:00:00',
                'updated_at' => '2026-04-20 08:00:00',
            ]);
        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $returnedBeforeReceiptPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::MISSING,
            'created_at' => '2026-04-21 07:30:00',
            'updated_at' => '2026-04-21 07:30:00',
        ]);
        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $returnedBeforeReceiptPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::RETURNED,
            'created_at' => '2026-04-22 08:45:00',
            'updated_at' => '2026-04-22 08:45:00',
        ]);

        $returnedAfterReceiptPurchase = $this->createFishBoxWithPurchase($mainBroker->id, $fishTypeId, 65.00, $mainUserId);
        $returnedAfterReceiptBox = $returnedAfterReceiptPurchase->fishBox;
        $returnedAfterReceiptBox->update(['box_status' => FishBoxStatusConstant::RETURNED]);
        InventoryLog::query()
            ->where('fish_box_purchase_id', $returnedAfterReceiptPurchase->id)
            ->update([
                'created_at' => '2026-04-20 08:00:00',
                'updated_at' => '2026-04-20 08:00:00',
            ]);
        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $returnedAfterReceiptPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::MISSING,
            'created_at' => '2026-04-22 11:15:00',
            'updated_at' => '2026-04-22 11:15:00',
        ]);
        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $returnedAfterReceiptPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::RETURNED,
            'created_at' => '2026-04-23 09:10:00',
            'updated_at' => '2026-04-23 09:10:00',
        ]);

        Model::preventLazyLoading();

        try {
            $brokers = $repository->getBrokersWithSalesDetails('2026-04-21', '2026-04-22', 'Main Stall');
            $mainBroker = $brokers->getCollection()->firstWhere('id', $dataset['brokers']['main']->id);

            $this->assertNotNull($mainBroker);
            $this->assertSame('main-broker@example.com', $mainBroker->user?->email);

            $sale = $mainBroker->sales->firstWhere('id', $dataset['sales']['sale_one']->id);

            $this->assertNotNull($sale);
            $this->assertSame('Ana Buyer', $sale->buyer_name);

            $detail = $sale->salesDetails->first();

            $this->assertNotNull($detail);
            $this->assertSame('bangus', $detail->item);
            $this->assertSame('Fish Box #01', $detail->fishBoxes()->first()?->name);
            $this->assertSame(FishBoxStatusConstant::SOLD, $detail->fishBoxes()->first()?->status);
            $this->assertTrue($mainBroker->relationLoaded('missingFishBoxesForReceipt'));
            $this->assertCount(2, $mainBroker->missingFishBoxesForReceipt);
            $this->assertTrue($mainBroker->missingFishBoxesForReceipt->pluck('id')->contains($carryOverMissingBox->id));
            $this->assertTrue($mainBroker->missingFishBoxesForReceipt->pluck('id')->contains($returnedAfterReceiptBox->id));
            $this->assertFalse($mainBroker->missingFishBoxesForReceipt->pluck('id')->contains($returnedBeforeReceiptBox->id));
            $this->assertTrue($mainBroker->missingFishBoxesForReceipt->firstWhere('id', $carryOverMissingBox->id)->relationLoaded('inventoryLogs'));
            $this->assertSame(
                FishBoxStatusConstant::MISSING,
                $mainBroker->missingFishBoxesForReceipt->firstWhere('id', $carryOverMissingBox->id)->inventoryLogs->first()?->status
            );
            $this->assertSame(
                FishBoxStatusConstant::MISSING,
                $mainBroker->missingFishBoxesForReceipt->firstWhere('id', $returnedAfterReceiptBox->id)->inventoryLogs->first()?->status
            );
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_admin_broker_receipt_snapshot_removes_boxes_once_they_are_returned(): void
    {
        $dataset = $this->seedSalesDataset();
        $repository = new SalesRepository();
        $mainBroker = $dataset['brokers']['main'];
        $mainUserId = $mainBroker->user_id;
        $fishTypeId = FishType::query()->where('name', 'bangus')->value('id');

        $missingPurchase = $this->createFishBoxWithPurchase($mainBroker->id, $fishTypeId, 72.00, $mainUserId);
        $missingBox = $missingPurchase->fishBox;
        $missingBox->update(['box_status' => FishBoxStatusConstant::RETURNED]);

        InventoryLog::query()
            ->where('fish_box_purchase_id', $missingPurchase->id)
            ->update([
                'created_at' => '2026-04-24 08:00:00',
                'updated_at' => '2026-04-24 08:00:00',
            ]);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $missingPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::MISSING,
            'created_at' => '2026-04-25 09:00:00',
            'updated_at' => '2026-04-25 09:00:00',
        ]);

        $beforeReturnSnapshot = $repository->getBrokerReceiptSnapshot(
            $mainBroker->id,
            '2026-04-01',
            '2026-04-25'
        );

        $this->assertNotNull($beforeReturnSnapshot);
        $this->assertCount(1, $beforeReturnSnapshot['missing_boxes']);
        $this->assertSame($missingBox->name, $beforeReturnSnapshot['missing_boxes'][0]['name']);

        InventoryLog::query()->insert([
            'fish_box_purchase_id' => $missingPurchase->id,
            'created_by_user_id' => $mainUserId,
            'status' => FishBoxStatusConstant::RETURNED,
            'created_at' => '2026-04-25 10:00:00',
            'updated_at' => '2026-04-25 10:00:00',
        ]);

        $afterReturnSnapshot = $repository->getBrokerReceiptSnapshot(
            $mainBroker->id,
            '2026-04-01',
            '2026-04-25'
        );

        $this->assertNotNull($afterReturnSnapshot);
        $this->assertCount(0, $afterReturnSnapshot['missing_boxes']);
    }

    /**
     * Seed a compact but realistic sales dataset used by the consistency checks.
     *
     * @return array<string, mixed>
     */
    private function seedSalesDataset(): array
    {
        $mainUser = User::create([
            'email' => 'main-broker@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $secondUser = User::create([
            'email' => 'second-broker@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $mainBroker = Broker::create([
            'user_id' => $mainUser->id,
            'first_name' => 'Main',
            'middle_name' => null,
            'last_name' => 'Broker',
            'address' => 'Main Market',
            'stall_name' => 'Main Stall',
            'broker_status' => 'Active',
        ]);

        $secondBroker = Broker::create([
            'user_id' => $secondUser->id,
            'first_name' => 'Second',
            'middle_name' => null,
            'last_name' => 'Broker',
            'address' => 'Second Market',
            'stall_name' => 'Second Stall',
            'broker_status' => 'Active',
        ]);

        $buyerOne = Buyer::create([
            'first_name' => 'Ana',
            'middle_name' => null,
            'last_name' => 'Buyer',
            'contact' => '09170000001',
        ]);

        $buyerTwo = Buyer::create([
            'first_name' => 'Ben',
            'middle_name' => null,
            'last_name' => 'Buyer',
            'contact' => '09170000002',
        ]);

        $buyerThree = Buyer::create([
            'first_name' => 'Cara',
            'middle_name' => null,
            'last_name' => 'Buyer',
            'contact' => '09170000003',
        ]);

        $bangus = FishType::create(['name' => 'bangus', 'description' => 'Milkfish']);
        $tuna = FishType::create(['name' => 'tuna', 'description' => 'Tuna']);

        $boxOne = $this->createFishBoxWithPurchase($mainBroker->id, $bangus->id, 50.00, $mainUser->id);
        $boxTwo = $this->createFishBoxWithPurchase($mainBroker->id, $bangus->id, 50.00, $mainUser->id);
        $boxThree = $this->createFishBoxWithPurchase($mainBroker->id, $tuna->id, 80.00, $mainUser->id);
        $boxFour = $this->createFishBoxWithPurchase($mainBroker->id, $bangus->id, 999.00, $mainUser->id);
        $boxFive = $this->createFishBoxWithPurchase($secondBroker->id, $tuna->id, 70.00, $secondUser->id);
        $boxSix = $this->createFishBoxWithPurchase($mainBroker->id, $bangus->id, 50.00, $mainUser->id);

        $saleOne = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $mainBroker->id,
            'buyer_id' => $buyerOne->id,
            'total_amount' => 100.00,
            'status' => SalesStatusConstant::ACTIVE,
        ]);

        $saleTwo = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $mainBroker->id,
            'buyer_id' => $buyerTwo->id,
            'total_amount' => 80.00,
            'status' => SalesStatusConstant::PAID,
        ]);

        $saleThree = Sales::create([
            'sales_date' => '2026-04-21',
            'broker_id' => $mainBroker->id,
            'buyer_id' => $buyerOne->id,
            'total_amount' => 50.00,
            'status' => SalesStatusConstant::PARTIALLY_PAID,
        ]);

        $deletedSale = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $mainBroker->id,
            'buyer_id' => $buyerOne->id,
            'total_amount' => 999.00,
            'status' => SalesStatusConstant::DELETED,
        ]);

        $secondBrokerSale = Sales::create([
            'sales_date' => '2026-04-22',
            'broker_id' => $secondBroker->id,
            'buyer_id' => $buyerThree->id,
            'total_amount' => 70.00,
            'status' => SalesStatusConstant::PARTIALLY_PAID,
        ]);

        SalesDetails::create([
            'sale_id' => $saleOne->id,
            'fish_box_purchase_id' => $boxOne->id,
            'unit_price' => 50.00,
            'sub_total' => 50.00,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $saleOne->id,
            'fish_box_purchase_id' => $boxTwo->id,
            'unit_price' => 50.00,
            'sub_total' => 50.00,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $saleTwo->id,
            'fish_box_purchase_id' => $boxThree->id,
            'unit_price' => 80.00,
            'sub_total' => 80.00,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $saleThree->id,
            'fish_box_purchase_id' => $boxSix->id,
            'unit_price' => 50.00,
            'sub_total' => 50.00,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $deletedSale->id,
            'fish_box_purchase_id' => $boxFour->id,
            'unit_price' => 999.00,
            'sub_total' => 999.00,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $secondBrokerSale->id,
            'fish_box_purchase_id' => $boxFive->id,
            'unit_price' => 70.00,
            'sub_total' => 70.00,
            'discount' => 0,
        ]);

        SalesPayment::create([
            'sale_id' => $saleOne->id,
            'paid_amount' => 40.00,
            'payment_date' => '2026-04-22',
            'payment_method' => 'Cash',
        ]);

        SalesPayment::create([
            'sale_id' => $saleTwo->id,
            'paid_amount' => 80.00,
            'payment_date' => '2026-04-22',
            'payment_method' => 'GCash',
        ]);

        SalesPayment::create([
            'sale_id' => $saleThree->id,
            'paid_amount' => 20.00,
            'payment_date' => '2026-04-21',
            'payment_method' => 'Cash',
        ]);

        SalesPayment::create([
            'sale_id' => $deletedSale->id,
            'paid_amount' => 999.00,
            'payment_date' => '2026-04-22',
            'payment_method' => 'Other',
        ]);

        SalesPayment::create([
            'sale_id' => $secondBrokerSale->id,
            'paid_amount' => 50.00,
            'payment_date' => '2026-04-22',
            'payment_method' => 'Bank Transfer',
        ]);

        return [
            'brokers' => [
                'main' => $mainBroker,
                'second' => $secondBroker,
            ],
            'sales' => [
                'sale_one' => $saleOne,
                'sale_three' => $saleThree,
            ],
        ];
    }

    private function createFishBoxWithPurchase(int $brokerId, int $fishTypeId, float $costPrice, int $userId): FishBoxPurchase
    {
        $fishBox = FishBox::create([
            'broker_id' => $brokerId,
            'qr_code' => (string) fake()->uuid(),
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        return FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $fishTypeId,
            'created_by_user_id' => $userId,
            'purchase_date' => '2026-04-22',
            'cost_price' => $costPrice,
        ]);
    }
}
