<?php

namespace Tests\Feature;

use App\Constants\FishBoxStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\Broker;
use App\Models\FishBox;
use App\Models\FishBoxPurchase;
use App\Models\FishType;
use App\Models\Role;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SalesBoxAvailabilityProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_same_box_cannot_be_sold_again_after_a_stale_second_submission(): void
    {
        [$user, $broker] = $this->createBrokerUser('stale-tab@example.com');
        $fishType = FishType::create([
            'name' => 'budlisan',
            'description' => 'Threadfin bream',
        ]);
        $fishBox = $this->createAvailableFishBox($broker, $user, $fishType, 'stale-tab-box', 1800.00);

        $payload = $this->makeSalesPayload($fishBox->id, $fishType->id, 'buyer-one');

        $this->actingAs($user);

        $firstResponse = $this->post('/broker/sales', $payload);
        $firstResponse->assertRedirect('/broker/sales');
        $firstResponse->assertSessionHas('success');

        $secondResponse = $this->post('/broker/sales', array_merge($payload, [
            'buyer_name' => 'buyer-two',
            'buyer_contact' => '09171234568',
        ]));

        $secondResponse->assertSessionHasErrors('sales_details');

        $fishBox->refresh();

        $this->assertSame(1, Sales::count());
        $this->assertSame(1, SalesDetails::count());
        $this->assertSame(FishBoxStatusConstant::SOLD, $fishBox->status);
    }

    public function test_same_box_cannot_be_submitted_twice_inside_one_transaction_payload(): void
    {
        [$user, $broker] = $this->createBrokerUser('duplicate-box@example.com');
        $fishType = FishType::create([
            'name' => 'galunggong',
            'description' => 'Round scad',
        ]);
        $fishBox = $this->createAvailableFishBox($broker, $user, $fishType, 'duplicate-payload-box', 950.00);

        $this->actingAs($user);

        $response = $this->post('/broker/sales', [
            'sales_date' => '2026-04-27',
            'buyer_name' => 'duplicate-buyer',
            'buyer_contact' => '09170000099',
            'total_amount' => 5000,
            'sales_details' => [
                [
                    'box_id' => [$fishBox->id],
                    'fish_type_id' => $fishType->id,
                    'unit_price' => 2500,
                    'quantity' => 1,
                    'sub_total' => 2500,
                ],
                [
                    'box_id' => [$fishBox->id],
                    'fish_type_id' => $fishType->id,
                    'unit_price' => 2500,
                    'quantity' => 1,
                    'sub_total' => 2500,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('sales_details');

        $this->assertSame(0, Sales::count());
        $this->assertSame(0, SalesDetails::count());
        $this->assertSame(FishBoxStatusConstant::IN_STOCK, $fishBox->fresh()->status);
    }

    public function test_manual_transaction_auto_assigns_available_box_on_save(): void
    {
        [$user, $broker] = $this->createBrokerUser('auto-assign@example.com');
        $fishType = FishType::create([
            'name' => 'budlisan',
            'description' => 'Threadfin bream',
        ]);
        $fishBox = $this->createAvailableFishBox($broker, $user, $fishType, 'auto-assign-box', 1800.00);

        $this->actingAs($user);

        $response = $this->post('/broker/sales', [
            'sales_date' => '2026-04-27',
            'buyer_name' => 'auto-buyer',
            'buyer_contact' => '09170000055',
            'total_amount' => 2500,
            'after_save' => 'transaction',
            'sales_details' => [
                [
                    'box_id' => [''],
                    'fish_type_id' => $fishType->id,
                    'unit_price' => 2500,
                    'quantity' => 1,
                    'sub_total' => 2500,
                ],
            ],
        ]);

        $response->assertRedirect(route('broker.transaction', [
            'modal' => 'print',
            'print' => 1,
            'auto_print' => 1,
        ], false));
        $response->assertSessionHas('success');

        $this->assertSame(1, Sales::count());
        $this->assertSame(1, SalesDetails::count());
        $this->assertSame($fishBox->id, SalesDetails::first()?->fishBoxPurchase?->fish_box_id);
        $this->assertSame(FishBoxStatusConstant::SOLD, $fishBox->fresh()->status);
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
            'first_name' => 'Protected',
            'middle_name' => null,
            'last_name' => 'Seller',
            'address' => 'Maramag',
            'stall_name' => 'Protected Stall',
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
    ): FishBox {
        $fishBox = FishBox::create([
            'broker_id' => $broker->id,
            'qr_code' => $qrCode,
            'box_status' => FishBoxStatusConstant::IN_STOCK,
        ]);

        FishBoxPurchase::create([
            'fish_box_id' => $fishBox->id,
            'fish_type_id' => $fishType->id,
            'created_by_user_id' => $user->id,
            'purchase_date' => '2026-04-27',
            'cost_price' => $costPrice,
        ]);

        return $fishBox;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeSalesPayload(int $fishBoxId, int $fishTypeId, string $buyerName): array
    {
        return [
            'sales_date' => '2026-04-27',
            'buyer_name' => $buyerName,
            'buyer_contact' => '09171234567',
            'total_amount' => 2500,
            'sales_details' => [
                [
                    'box_id' => [$fishBoxId],
                    'fish_type_id' => $fishTypeId,
                    'unit_price' => 2500,
                    'quantity' => 1,
                    'sub_total' => 2500,
                ],
            ],
        ];
    }
}
