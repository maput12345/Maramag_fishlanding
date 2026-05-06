<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Constants\SalesStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\Broker;
use App\Models\Buyer;
use App\Models\FinancialStatementEntry;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishType;
use App\Models\Role;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\SalesPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FinancialStatementFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-25 08:30:00'));
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_daily_financial_statement_uses_sales_costs_and_manual_adjustments(): void
    {
        [$user, $broker] = $this->createBrokerUser('finance-broker@example.com', 'Daily', 'Broker', 'Finance Stall');
        $dataset = $this->seedDailyStatementDataset($broker, $user);

        FinancialStatementEntry::create([
            'broker_id' => $broker->id,
            'created_by_user_id' => $user->id,
            'statement_date' => '2026-04-25',
            'entry_type' => FinancialStatementEntry::TYPE_SGA,
            'description' => 'Helpers allowance',
            'amount' => 150,
        ]);

        FinancialStatementEntry::create([
            'broker_id' => $broker->id,
            'created_by_user_id' => $user->id,
            'statement_date' => '2026-04-25',
            'entry_type' => FinancialStatementEntry::TYPE_LOSS_ON_SALE,
            'description' => 'Damaged fish adjustment',
            'amount' => 50,
        ]);

        $statement = FinancialStatementEntry::getDailyStatement($broker->id, '2026-04-25');
        $salesBreakdown = FinancialStatementEntry::getDailySalesBreakdown($broker->id, '2026-04-25');

        $this->assertEqualsWithDelta(500.0, $statement['sales'], 0.01);
        $this->assertSame(2, $statement['sales_count']);
        $this->assertEqualsWithDelta(300.0, $statement['cost_of_sales'], 0.01);
        $this->assertSame(3, $statement['sold_boxes']);
        $this->assertEqualsWithDelta(200.0, $statement['gross_profit'], 0.01);
        $this->assertEqualsWithDelta(150.0, $statement['selling_general_and_administrative_expenses'], 0.01);
        $this->assertEqualsWithDelta(50.0, $statement['operating_income'], 0.01);
        $this->assertEqualsWithDelta(50.0, $statement['loss_on_sale'], 0.01);
        $this->assertEqualsWithDelta(0.0, $statement['net_income'], 0.01);
        $this->assertEqualsWithDelta(0.0, $statement['cash_on_hand'], 0.01);
        $this->assertCount(2, $salesBreakdown);
        $this->assertSame(
            collect([$dataset['sale_one']->formatted_id, $dataset['sale_two']->formatted_id])->sort()->values()->all(),
            $salesBreakdown->pluck('formatted_id')->sort()->values()->all()
        );
    }

    public function test_broker_can_add_and_remove_daily_financial_statement_entries(): void
    {
        [$user, $broker] = $this->createBrokerUser('finance-actions@example.com', 'Entry', 'Manager', 'Entry Stall');

        $this->actingAs($user);

        $storeResponse = $this->post(route('broker.financial-statements.entries.store', [], false), [
            'statement_date' => '2026-04-25',
            'entry_type' => FinancialStatementEntry::TYPE_SGA,
            'description' => 'Ice delivery',
            'amount' => 80.50,
        ]);

        $storeResponse->assertRedirect(route('broker.financial-statements.index', [
            'statement_date' => '2026-04-25',
        ]));

        $entry = FinancialStatementEntry::query()->first();

        $this->assertNotNull($entry);
        $this->assertSame($broker->id, $entry->broker_id);
        $this->assertSame('Ice delivery', $entry->description);

        $deleteResponse = $this->delete(route('broker.financial-statements.entries.destroy', [
            'entry' => $entry,
            'statement_date' => '2026-04-25',
        ], false));

        $deleteResponse->assertRedirect(route('broker.financial-statements.index', [
            'statement_date' => '2026-04-25',
        ]));

        $this->assertDatabaseCount('FinancialStatementEntry', 0);
    }

    public function test_financial_statement_page_renders_for_broker_with_daily_context(): void
    {
        [$user, $broker] = $this->createBrokerUser('finance-page@example.com', 'Page', 'Viewer', 'Page Stall');
        $this->seedDailyStatementDataset($broker, $user);

        FinancialStatementEntry::create([
            'broker_id' => $broker->id,
            'created_by_user_id' => $user->id,
            'statement_date' => '2026-04-25',
            'entry_type' => FinancialStatementEntry::TYPE_SGA,
            'description' => 'Fuel',
            'amount' => 35,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('broker.financial-statements.index', [
            'statement_date' => '2026-04-25',
        ], false));

        $response->assertOk();
        $response->assertSee('Financial Statement');
        $response->assertSee('Daily Finance');
        $response->assertSee('Selling, General and Administrative Expenses');
        $response->assertSee('Fuel');
        $response->assertSee('Sales Revenue');
        $response->assertSee('Cash on Hand');
    }

    public function test_daily_financial_statement_includes_outstanding_receivable_balance_as_of_statement_date(): void
    {
        [$user, $broker] = $this->createBrokerUser('finance-balance@example.com', 'Balance', 'Viewer', 'Balance Stall');
        $dataset = $this->seedDailyStatementDataset($broker, $user);

        SalesPayment::create([
            'sale_id' => $dataset['sale_one']->id,
            'paid_amount' => 80,
            'payment_date' => '2026-04-25',
            'payment_method' => 'Cash',
        ]);

        SalesPayment::create([
            'sale_id' => $dataset['sale_two']->id,
            'paid_amount' => 20,
            'payment_date' => '2026-04-26',
            'payment_method' => 'Cash',
        ]);

        $statementApr25 = FinancialStatementEntry::getDailyStatement($broker->id, '2026-04-25');
        $statementApr26 = FinancialStatementEntry::getDailyStatement($broker->id, '2026-04-26');

        $this->assertEqualsWithDelta(420.0, $statementApr25['outstanding_receivable_balance'], 0.01);
        $this->assertEqualsWithDelta(400.0, $statementApr26['outstanding_receivable_balance'], 0.01);
        $this->assertEqualsWithDelta(100.0, $statementApr25['cash_on_hand'], 0.01);
        $this->assertEqualsWithDelta(0.0, $statementApr26['cash_on_hand'], 0.01);
    }

    /**
     * @return array{0: User, 1: Broker}
     */
    private function createBrokerUser(string $email, string $firstName, string $lastName, string $stallName): array
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
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'address' => $stallName . ' Address',
            'stall_name' => $stallName,
            'broker_status' => 'Active',
        ]);

        return [$user, $broker];
    }

    /**
     * @return array{sale_one: Sales, sale_two: Sales}
     */
    private function seedDailyStatementDataset(Broker $broker, User $user): array
    {
        $bangus = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $tuna = FishType::create([
            'name' => 'tuna',
            'description' => 'Tuna',
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

        $boxOne = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'finance-box-1',
            'box_status' => 'Sold',
        ]);

        $boxTwo = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'finance-box-2',
            'box_status' => 'Sold',
        ]);

        $boxThree = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'finance-box-3',
            'box_status' => 'Sold',
        ]);

        $purchaseOne = FishBoxPurchase::create([
            'fish_box_id' => $boxOne->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-25',
            'cost_price' => 80,
        ]);

        $purchaseTwo = FishBoxPurchase::create([
            'fish_box_id' => $boxTwo->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-25',
            'cost_price' => 90,
        ]);

        $purchaseThree = FishBoxPurchase::create([
            'fish_box_id' => $boxThree->id,
            'fish_type_id' => $tuna->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-25',
            'cost_price' => 130,
        ]);

        $saleOne = Sales::create([
            'sales_date' => '2026-04-25',
            'broker_id' => $broker->id,
            'buyer_id' => $buyerOne->id,
            'total_amount' => 320,
            'status' => SalesStatusConstant::PAID,
        ]);

        $saleTwo = Sales::create([
            'sales_date' => '2026-04-25',
            'broker_id' => $broker->id,
            'buyer_id' => $buyerTwo->id,
            'total_amount' => 180,
            'status' => SalesStatusConstant::ACTIVE,
        ]);

        SalesDetails::create([
            'sale_id' => $saleOne->id,
            'fish_box_purchase_id' => $purchaseOne->id,
            'unit_price' => 120,
            'sub_total' => 120,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $saleOne->id,
            'fish_box_purchase_id' => $purchaseTwo->id,
            'unit_price' => 200,
            'sub_total' => 200,
            'discount' => 0,
        ]);

        SalesDetails::create([
            'sale_id' => $saleTwo->id,
            'fish_box_purchase_id' => $purchaseThree->id,
            'unit_price' => 180,
            'sub_total' => 180,
            'discount' => 0,
        ]);

        return [
            'sale_one' => $saleOne->fresh(),
            'sale_two' => $saleTwo->fresh(),
        ];
    }
}
