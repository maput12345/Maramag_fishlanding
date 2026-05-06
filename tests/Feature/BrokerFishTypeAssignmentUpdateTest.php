<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Broker\FishTypesController;
use App\Http\Requests\FishTypeRequest;
use App\Models\Broker;
use App\Models\BrokerFishType;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishPrice;
use App\Models\FishType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokerFishTypeAssignmentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_unused_fish_to_new_name_moves_assignment_to_new_fish_type(): void
    {
        [$user, $broker] = $this->createBrokerUser('fish-update@example.com');

        $budlisan = FishType::create([
            'name' => 'Budlisan',
            'description' => 'Original fish',
        ]);

        $assignment = BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $budlisan->id,
            'display_name' => 'Budlisan',
            'display_description' => 'Original fish',
        ]);

        $this->actingAs($user);

        $response = app(FishTypesController::class)->update($this->makeFishTypeUpdateRequest($budlisan->id, [
            'name' => 'Bangus',
            'description' => 'Milkfish',
        ], $user), $budlisan->id);

        $this->assertSame(route('broker.inventory.index', ['tab' => 'fishTypes']), $response->getTargetUrl());

        $bangus = FishType::where('name', 'Bangus')->first();

        $this->assertNotNull($bangus);
        $this->assertSame('Budlisan', $budlisan->fresh()->name);
        $this->assertSame($bangus->id, $assignment->fresh()->fish_type_id);
        $this->assertSame('Bangus', $assignment->fresh()->display_name);
    }

    public function test_editing_used_fish_to_different_name_is_blocked_to_preserve_history(): void
    {
        [$user, $broker] = $this->createBrokerUser('fish-used-update@example.com');

        $budlisan = FishType::create([
            'name' => 'Budlisan',
            'description' => 'Original fish',
        ]);

        $assignment = BrokerFishType::create([
            'broker_id' => $broker->id,
            'fish_type_id' => $budlisan->id,
            'display_name' => 'Budlisan',
        ]);

        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => 'used-fish-box',
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $budlisan->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-05-06',
            'cost_price' => 100,
        ]);

        FishPrice::create([
            'broker_fish_type_id' => $assignment->id,
            'price' => 150,
            'price_date' => '2026-05-06',
        ]);

        $this->actingAs($user);

        $response = app(FishTypesController::class)->update($this->makeFishTypeUpdateRequest($budlisan->id, [
            'name' => 'Bangus',
            'description' => 'Milkfish',
        ], $user), $budlisan->id);

        $this->assertSame(route('broker.inventory.index', ['tab' => 'fishTypes']), $response->getTargetUrl());
        $this->assertSame(
            'This fish is already used in purchases or prices. Add the new fish instead to keep history accurate.',
            session('error')
        );

        $this->assertSame($budlisan->id, $assignment->fresh()->fish_type_id);
        $this->assertNull(FishType::where('name', 'Bangus')->first());
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
            'first_name' => 'Fish',
            'middle_name' => null,
            'last_name' => 'Broker',
            'address' => 'Maramag',
            'stall_name' => 'Fish Stall',
            'broker_status' => 'Active',
        ]);

        return [$user, $broker];
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
