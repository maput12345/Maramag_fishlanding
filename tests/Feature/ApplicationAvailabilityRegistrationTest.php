<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Models\ApplicationOpening;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApplicationAvailabilityRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_login_apply_button_is_disabled_without_available_stall(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('No vacant stall available');
        $response->assertDontSee('Apply here!');
        $response->assertDontSee('href="' . route('register') . '"', false);
    }

    public function test_login_apply_button_is_enabled_when_stall_is_open_for_application(): void
    {
        $this->createAvailableOpening();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Apply here!');
        $response->assertSee('href="' . route('register') . '"', false);
        $response->assertDontSee('No vacant stall available');
    }

    public function test_registration_page_redirects_when_no_stall_is_available(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'No vacant stall is open for applications right now.');
    }

    public function test_direct_registration_post_is_blocked_when_no_stall_is_available(): void
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'email' => 'blocked-applicant@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertSessionHasErrors('availability');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked-applicant@example.com',
        ]);
    }

    public function test_registration_creates_applicant_account_when_stall_is_available(): void
    {
        $this->createAvailableOpening();

        $response = $this->post('/register', [
            'email' => 'new-applicant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/applications');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-applicant@example.com',
        ]);
    }

    private function createAvailableOpening(): ApplicationOpening
    {
        $admin = User::createUserWithRole(
            [
                'email' => 'availability-admin@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Admin',
            ]
        );

        $stall = Stall::create([
            'stall_number' => 'A-1',
            'stall_status' => 'Open for Application',
        ]);

        return ApplicationOpening::create([
            'stall_id' => $stall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'bidding_date' => now()->addDays(3)->toDateString(),
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);
    }
}
