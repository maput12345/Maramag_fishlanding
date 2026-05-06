<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Broker\FishPricesController;
use App\Http\Requests\FishPriceRequest;
use App\Models\Broker;
use App\Models\BrokerFishType;
use App\Models\FishPrice;
use App\Models\FishPriceRecord;
use App\Models\FishType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FishPriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_updating_price_creates_history_record_instead_of_overwriting_latest_price(): void
    {
        [$user, $assignment] = $this->createPricedAssignment();

        $this->actingAs($user);

        app(FishPricesController::class)->update($this->makeFishPriceUpdateRequest($assignment->id, [
            'price' => 4500,
            'default_cost_price' => 3000,
            'price_date' => '2026-05-06',
        ], $user), $assignment->id);

        $records = FishPriceRecord::where('broker_fish_type_id', $assignment->id)
            ->orderBy('price_date')
            ->get();

        $this->assertCount(2, $records);
        $this->assertSame('3000.00', $records[0]->price);
        $this->assertSame('2000.00', $records[0]->default_cost_price);
        $this->assertSame('4500.00', $records[1]->price);
        $this->assertSame('3000.00', $records[1]->default_cost_price);
    }

    public function test_price_history_data_is_loaded_newest_first(): void
    {
        [$user, $assignment] = $this->createPricedAssignment();

        FishPrice::create([
            'broker_fish_type_id' => $assignment->id,
            'price' => 4500,
            'default_cost_price' => 3000,
            'price_date' => '2026-05-06',
        ]);

        $this->actingAs($user);

        $request = Request::create('/broker/inventory', 'GET', [
            'tab' => 'fishPrices',
            'modal' => 'history',
            'history' => $assignment->id,
        ]);

        $data = app(FishPricesController::class)->getIndexData($request);
        $history = $data['historyBrokerFishType']->prices;

        $this->assertSame(['4500.00', '3000.00'], $history->pluck('price')->all());
        $this->assertSame(['3000.00', '2000.00'], $history->pluck('default_cost_price')->all());

        $response = $this->get('/broker/inventory?' . http_build_query([
            'tab' => 'fishPrices',
            'modal' => 'history',
            'history' => $assignment->id,
        ]));

        $response->assertOk();
        $response->assertSee('Price History');
        $response->assertSee('PHP 4,500.00');
        $response->assertSee('PHP 3,000.00');
        $response->assertSee('PHP 2,000.00');
    }

    public function test_price_history_can_be_filtered_by_date(): void
    {
        [$user, $assignment] = $this->createPricedAssignment();

        FishPrice::create([
            'broker_fish_type_id' => $assignment->id,
            'price' => 4500,
            'default_cost_price' => 3000,
            'price_date' => '2026-05-06',
        ]);

        $this->actingAs($user);

        $request = Request::create('/broker/inventory', 'GET', [
            'tab' => 'fishPrices',
            'modal' => 'history',
            'history' => $assignment->id,
            'history_date' => '2026-05-06',
        ]);

        $data = app(FishPricesController::class)->getIndexData($request);

        $this->assertSame(['4500.00'], $data['historyBrokerFishType']->prices->pluck('price')->all());

        $response = $this->get('/broker/inventory?' . http_build_query([
            'tab' => 'fishPrices',
            'modal' => 'history',
            'history' => $assignment->id,
            'history_date' => '2026-05-06',
        ]));

        $response->assertOk();
        $response->assertSee('type="date"', false);
        $response->assertSee('PHP 4,500.00');
        $response->assertDontSee('PHP 2,000.00');
    }

    /**
     * @return array{0: User, 1: BrokerFishType}
     */
    private function createPricedAssignment(): array
    {
        $user = User::create([
            'email' => 'fish-price-history@example.com',
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);

        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        $broker = Broker::create([
            'user_id' => $user->id,
            'first_name' => 'Price',
            'middle_name' => null,
            'last_name' => 'History',
            'address' => 'Maramag',
            'stall_name' => 'History Stall',
            'broker_status' => 'Active',
        ]);

        $fishType = FishType::create([
            'name' => 'Budlisan',
            'description' => 'Original fish',
        ]);

        $assignment = BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $fishType->id,
            'display_name' => 'Budlisan',
        ]);

        FishPrice::create([
            'broker_fish_type_id' => $assignment->id,
            'price' => 3000,
            'default_cost_price' => 2000,
            'price_date' => '2026-04-29',
        ]);

        return [$user, $assignment];
    }

    private function makeFishPriceUpdateRequest(int $assignmentId, array $payload, User $user): FishPriceRequest
    {
        $request = FishPriceRequest::create(
            '/broker/fish-prices/' . $assignmentId,
            'PUT',
            $payload,
            [],
            [],
            [
                'HTTP_REFERER' => route('broker.inventory.index', [
                    'tab' => 'fishPrices',
                    'modal' => 'edit',
                    'edit' => $assignmentId,
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
