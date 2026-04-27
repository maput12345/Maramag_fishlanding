<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\Broker;
use App\Models\BrokerFishType;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishPrice;
use App\Models\FishType;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SalesFormPriceAutofillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-25 09:00:00'));
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sales_form_config_only_includes_fish_types_with_saved_selling_prices(): void
    {
        [$user, $broker] = $this->createBrokerUser('sales-pricing@example.com');

        $pricedFishType = FishType::create([
            'name' => 'tilapia',
            'description' => 'Tilapia',
        ]);

        $unpricedFishType = FishType::create([
            'name' => 'bangus',
            'description' => 'Milkfish',
        ]);

        $pricedAssignment = BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $pricedFishType->id,
        ]);

        BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $unpricedFishType->id,
        ]);

        FishPrice::create([
            'broker_fish_type_id' => $pricedAssignment->id,
            'price' => 180.50,
            'default_cost_price' => 120.00,
            'price_date' => '2026-04-25',
        ]);

        $this->createAvailableFishBox($broker, $user, $pricedFishType, 'priced-box', 125.00);
        $this->createAvailableFishBox($broker, $user, $unpricedFishType, 'unpriced-box', 115.00);

        $this->actingAs($user);

        $response = $this->get(route('broker.sales.sales', [
            'modal' => 'create',
        ], false));

        $response->assertOk();
        $response->assertSee('Transaction');
        $response->assertSee('Price per box auto-fills from your current broker fish price list when available.');
        $response->assertSee('data-suggested-price="180.5"', false);
        $response->assertSee('data-currency-input="true"', false);

        $config = $this->extractSalesFormConfig($response->getContent());

        $this->assertSame('create', $config['mode']);
        $this->assertSame(180.5, $config['fishPrices'][(string) $pricedFishType->id]);
        $this->assertArrayNotHasKey((string) $unpricedFishType->id, $config['fishPrices']);
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
            'first_name' => 'Auto',
            'middle_name' => null,
            'last_name' => 'Pricing',
            'address' => 'Maramag',
            'stall_name' => 'Auto Pricing Stall',
            'broker_status' => 'Active',
        ]);

        return [$user, $broker];
    }

    private function createAvailableFishBox(
        Broker $broker,
        User $user,
        FishType $fishType,
        string $qrCode,
        float $costPrice
    ): void {
        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => $qrCode,
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-25',
            'cost_price' => $costPrice,
        ]);
    }

    private function extractSalesFormConfig(string $html): array
    {
        $matched = preg_match(
            '/<script type="application\/json" data-sales-form-config>(.*?)<\/script>/s',
            $html,
            $matches
        );

        $this->assertSame(1, $matched, 'Sales form config script was not rendered.');

        $config = json_decode($matches[1], true);

        $this->assertIsArray($config);

        return $config;
    }
}
