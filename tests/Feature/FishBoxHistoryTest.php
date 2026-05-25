<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Broker\FishBoxController;
use App\Models\Broker;
use App\Models\BrokerFishType;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FishBoxHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_fish_box_history_loads_stock_cycles_newest_first(): void
    {
        [$user, $fishBox] = $this->createFishBoxWithHistory();

        $this->actingAs($user);

        $request = Request::create('/broker/inventory', 'GET', [
            'tab' => 'fishBoxes',
            'modal' => 'history',
            'history' => $fishBox->id,
        ]);

        $data = app(FishBoxController::class)->getIndexData($request);
        $history = $data['historyFishBox']->purchases;

        $this->assertSame(['Lapu-lapu', 'Budlisan', 'Bangus'], $history->map(fn ($cycle) => $cycle->fishType->name)->all());
        $this->assertSame(['5000.00', '3000.00', '2000.00'], $history->pluck('cost_price')->all());

        $response = $this->get('/broker/inventory?' . http_build_query([
            'tab' => 'fishBoxes',
            'modal' => 'history',
            'history' => $fishBox->id,
        ]));

        $response->assertOk();
        $response->assertSee('Fish Box History');
        $response->assertSee('Lapu-lapu');
        $response->assertSee('Budlisan');
        $response->assertSee('Bangus');
    }

    public function test_fish_box_history_can_be_filtered_by_date(): void
    {
        [$user, $fishBox] = $this->createFishBoxWithHistory();

        $this->actingAs($user);

        $request = Request::create('/broker/inventory', 'GET', [
            'tab' => 'fishBoxes',
            'modal' => 'history',
            'history' => $fishBox->id,
            'box_history_date_from' => '2026-04-29',
            'box_history_date_to' => '2026-04-29',
        ]);

        $data = app(FishBoxController::class)->getIndexData($request);

        $this->assertSame(['Budlisan'], $data['historyFishBox']->purchases->map(fn ($cycle) => $cycle->fishType->name)->all());

        $response = $this->get('/broker/inventory?' . http_build_query([
            'tab' => 'fishBoxes',
            'modal' => 'history',
            'history' => $fishBox->id,
            'box_history_date_from' => '2026-04-29',
            'box_history_date_to' => '2026-04-29',
        ]));

        $response->assertOk();
        $response->assertSee('type="date"', false);
        $response->assertSee('Budlisan');
    }

    public function test_returned_boxes_are_cleared_to_unassigned_until_restocked(): void
    {
        [$user, $fishBox] = $this->createFishBoxWithHistory();
        $brokerId = Broker::getBrokerIdByUserId($user->id);

        $fishBox->update(['box_status' => FishBoxStatusConstant::RETURNED]);

        $clearedCount = FishBox::returnAllToStock($brokerId, $user->id);

        $fishBox->refresh()->load('currentPurchase.fishType');

        $this->assertSame(1, $clearedCount);
        $this->assertSame(FishBoxStatusConstant::UNASSIGNED, $fishBox->status);
        $this->assertNull($fishBox->fish_type_id);
        $this->assertNull($fishBox->fish_type_name);
        $this->assertNull($fishBox->cost_price);
        $this->assertNotNull($fishBox->currentPurchase);
        $this->assertFalse($fishBox->inventoryLogs()->where('status', FishBoxStatusConstant::UNASSIGNED)->exists());
    }

    /**
     * @return array{0: User, 1: FishBox}
     */
    private function createFishBoxWithHistory(): array
    {
        $user = User::create([
            'email' => 'fish-box-history@example.com',
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);

        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $broker = Broker::create([
            'user_id' => $user->id,
            'first_name' => 'Box',
            'middle_name' => null,
            'last_name' => 'History',
            'address' => 'Maramag',
            'stall_name' => 'Box History Stall',
            'broker_status' => 'Active',
        ]);

        $bangus = FishType::create(['name' => 'Bangus', 'description' => 'Milkfish']);
        $budlisan = FishType::create(['name' => 'Budlisan', 'description' => 'Original fish']);
        $lapulapu = FishType::create(['name' => 'Lapu-lapu', 'description' => 'Grouper']);

        foreach ([$bangus, $budlisan, $lapulapu] as $fishType) {
            BrokerFishType::create([
                'broker_id' => $broker->id,
                'fish_type_id' => $fishType->id,
                'display_name' => $fishType->name,
            ]);
        }

        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'box-history-qr',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $bangus->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-22',
            'cost_price' => 2000,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $budlisan->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-29',
            'cost_price' => 3000,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $lapulapu->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-05-06',
            'cost_price' => 5000,
        ]);

        return [$user, $fishBox];
    }
}
