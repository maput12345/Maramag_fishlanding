<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\SalesStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\Broker;
use App\Models\Buyer;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishType;
use App\Models\Role;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BrokerReceiptAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-25 10:00:00'));
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_broker_receipt_groups_same_commodity_lines_with_same_price(): void
    {
        [$user, $broker] = $this->createBrokerUser('receipt-grouping@example.com');

        $bangus = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $purchaseOne = $this->createSoldFishBoxPurchase($broker, $user, $bangus, 'receipt-box-1', 30.00);
        $purchaseTwo = $this->createSoldFishBoxPurchase($broker, $user, $bangus, 'receipt-box-2', 30.00);
        $buyer = Buyer::create([
            'first_name' => 'Buyer',
            'middle_name' => null,
            'last_name' => 'Receipt',
            'contact' => '09170000011',
        ]);

        $sale = Sales::create([
            'sales_date' => '2026-04-25 09:30:00',
            'broker_id' => $broker->id,
            'buyer_id' => $buyer->id,
            'total_amount' => 100.00,
            'status' => SalesStatusConstant::ACTIVE,
        ]);

        SalesDetails::create([
            'sale_id' => $sale->id,
            'fish_box_purchase_id' => $purchaseOne->id,
            'unit_price' => 50.00,
            'sub_total' => 50.00,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $sale->id,
            'fish_box_purchase_id' => $purchaseTwo->id,
            'unit_price' => 50.00,
            'sub_total' => 50.00,
            'discount' => 0,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('broker.sales.sales', [
            'modal' => 'print',
            'print' => $sale->id,
        ], false));

        $response->assertOk();
        $response->assertSee('Commodities Sold');
        $response->assertSee('2 x PHP 50.00');
        $response->assertSee('PHP 100.00');
        $response->assertSee('Fish Box #01');
        $response->assertSee('Fish Box #02');
    }

    /**
     * @return array{0: User, 1: Broker}
     */
    private function createBrokerUser(string $email): array
    {
        $user = User::create([
            'email' => $email,
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);

        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $broker = Broker::create([
            'user_id' => $user->id,
            'first_name' => 'Receipt',
            'middle_name' => null,
            'last_name' => 'Broker',
            'address' => 'Maramag',
            'stall_name' => 'Receipt Stall',
            'broker_status' => 'Active',
        ]);

        return [$user, $broker];
    }

    private function createSoldFishBoxPurchase(
        Broker $broker,
        User $user,
        FishType $fishType,
        string $qrCode,
        float $costPrice
    ): FishBoxPurchase {
        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => $qrCode,
            'box_status' => FishBoxStatusConstant::SOLD,
        ]);

        return FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-25',
            'cost_price' => $costPrice,
        ]);
    }
}
