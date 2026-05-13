<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Models\ApplicationOpening;
use App\Models\RequirementType;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        $response->assertDontSee('View Stalls and Requirements');
        $response->assertSee('Application Requirements');
        $response->assertSee('Barangay Clearance and Community Tax Certificate');
        $response->assertSee('No vacant stall available');
        $response->assertDontSee('Apply here!');
        $response->assertDontSee('href="' . route('register') . '"', false);
    }

    public function test_login_apply_button_is_enabled_when_stall_is_open_for_application(): void
    {
        $this->createAvailableOpening();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('View Stalls and Requirements');
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
                'first_name' => 'Blocked',
                'last_name' => 'Applicant',
                'email' => 'blocked-applicant@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertSessionHasErrors('availability');
        $this->assertGuest();
        $this->assertDatabaseMissing('User', [
            'email' => 'blocked-applicant@example.com',
        ]);
    }

    public function test_registration_creates_applicant_account_when_stall_is_available(): void
    {
        Notification::fake();
        $this->createAvailableOpening();

        $response = $this->post('/register', [
            'first_name' => 'New',
            'middle_name' => 'Account',
            'last_name' => 'Applicant',
            'suffix' => 'Jr.',
            'email' => 'new-applicant@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('User', [
            'email' => 'new-applicant@gmail.com',
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('ApplicantProfile', [
            'first_name' => 'New',
            'middle_name' => 'Account',
            'last_name' => 'Applicant',
            'suffix' => 'Jr.',
        ]);
        Notification::assertSentTo(User::where('email', 'new-applicant@gmail.com')->first(), VerifyEmail::class);
    }

    public function test_registration_rejects_email_domains_that_cannot_receive_mail(): void
    {
        $this->createAvailableOpening();

        $response = $this
            ->from('/register')
            ->post('/register', [
                'first_name' => 'Invalid',
                'last_name' => 'Domain',
                'email' => 'invalid-domain@example.invalid',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertSessionHasErrors([
            'email' => 'Please enter a valid email address.',
        ]);
        $this->assertGuest();
        $this->assertDatabaseMissing('User', [
            'email' => 'invalid-domain@example.invalid',
        ]);
    }

    public function test_unverified_applicant_must_verify_email_before_opening_applications(): void
    {
        $applicant = User::createUserWithRole([
            'email' => 'needs-verification@example.com',
            'email_verified_at' => null,
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Needs',
            'last_name' => 'Verification',
        ]);

        $response = $this->actingAs($applicant)->get('/applications');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_guest_verification_link_verifies_applicant_and_continues_to_applications(): void
    {
        $applicant = User::createUserWithRole([
            'email' => 'verify-from-email@example.com',
            'email_verified_at' => null,
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Verify',
            'last_name' => 'Link',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $applicant->id,
                'hash' => sha1($applicant->getEmailForVerification()),
            ]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/applications');
        $this->assertAuthenticatedAs($applicant);
        $this->assertNotNull($applicant->fresh()->email_verified_at);
    }

    public function test_registration_rejects_invalid_email_format_with_clear_message(): void
    {
        $this->createAvailableOpening();

        $response = $this
            ->from('/register')
            ->post('/register', [
                'first_name' => 'Typo',
                'last_name' => 'Applicant',
                'email' => 'not-an-email',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertSessionHasErrors([
            'email' => 'Please enter a valid email address.',
        ]);
        $this->assertGuest();
        $this->assertDatabaseMissing('User', [
            'email' => 'not-an-email',
        ]);
    }

    public function test_old_unverified_applicant_accounts_are_pruned(): void
    {
        $staleApplicant = User::createUserWithRole([
            'email' => 'stale-applicant@example.com',
            'email_verified_at' => null,
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Stale',
            'last_name' => 'Applicant',
        ]);
        $staleApplicant->forceFill([
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ])->save();

        $freshApplicant = User::createUserWithRole([
            'email' => 'fresh-applicant@example.com',
            'email_verified_at' => null,
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Fresh',
            'last_name' => 'Applicant',
        ]);

        $verifiedApplicant = User::createUserWithRole([
            'email' => 'verified-applicant@example.com',
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Verified',
            'last_name' => 'Applicant',
        ]);
        $verifiedApplicant->forceFill([
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ])->save();

        $this->artisan('applicants:prune-unverified --days=7')
            ->expectsOutput('Deleted 1 stale unverified applicant account(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('User', ['id' => $staleApplicant->id]);
        $this->assertDatabaseHas('User', ['id' => $freshApplicant->id]);
        $this->assertDatabaseHas('User', ['id' => $verifiedApplicant->id]);
    }

    public function test_login_page_shows_available_stall_and_requirements_preview_before_account_creation(): void
    {
        $opening = $this->createAvailableOpening();
        $requirementType = RequirementType::create([
            'requirement_name' => 'Police Clearance',
            'description' => 'Bring one original and one photocopy.',
            'is_required' => true,
            'audience' => RequirementType::APPLICANT_TYPE_BOTH,
            'sort_order' => 10,
        ]);

        $opening->requirementTypes()->sync([
            $requirementType->id => [
                'is_required' => true,
                'audience' => RequirementType::APPLICANT_TYPE_BOTH,
                'sort_order' => 10,
            ],
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('View Stalls and Requirements');
        $response->assertSee('Available Stalls');
        $response->assertSee('Application Requirements');
        $response->assertSee('Stall A-1');
        $response->assertSee('Police Clearance');
        $response->assertSee('Bring one original and one photocopy.');
    }

    public function test_registration_page_keeps_create_account_form_without_preview_sections(): void
    {
        $this->createAvailableOpening();

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('Create Applicant Account');
        $response->assertDontSee('Available Stalls');
        $response->assertDontSee('Application Requirements');
    }

    public function test_applicant_can_update_profile_without_writing_identity_to_user_table(): void
    {
        $applicant = User::createUserWithRole([
            'email' => 'profile-applicant@example.com',
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Original',
            'middle_name' => 'Middle',
            'last_name' => 'Applicant',
            'suffix' => null,
        ]);

        $response = $this->actingAs($applicant)->put('/profile', [
            'first_name' => 'Updated',
            'middle_name' => 'Edited',
            'last_name' => 'Profile',
            'suffix' => 'Sr.',
            'contact_number' => '09170000000',
            'address' => 'Maramag, Bukidnon',
            'password_option' => 'keep',
        ]);

        $response->assertRedirect(route('applications.index'));

        $this->assertDatabaseHas('ApplicantProfile', [
            'user_id' => $applicant->id,
            'first_name' => 'Updated',
            'middle_name' => 'Edited',
            'last_name' => 'Profile',
            'suffix' => 'Sr.',
            'contact_number' => '09170000000',
            'address' => 'Maramag, Bukidnon',
        ]);
        $this->assertDatabaseHas('User', ['id' => $applicant->id]);
    }

    public function test_application_form_autofills_from_updated_applicant_profile(): void
    {
        $opening = $this->createAvailableOpening();
        $applicant = User::createUserWithRole([
            'email' => 'autofill-applicant@example.com',
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ], [
            'first_name' => 'Before',
            'last_name' => 'Applicant',
        ]);

        $this->actingAs($applicant)->put('/profile', [
            'first_name' => 'After',
            'middle_name' => 'Profile',
            'last_name' => 'Update',
            'suffix' => 'Jr.',
            'contact_number' => '09171112222',
            'address' => 'Updated Address',
            'password_option' => 'keep',
        ]);

        $response = $this->actingAs($applicant)
            ->get('/applications/openings/' . $opening->getRouteKey());

        $response->assertOk();
        $response->assertSee('value="After"', false);
        $response->assertSee('value="Profile"', false);
        $response->assertSee('value="Update"', false);
        $response->assertSee('value="Jr."', false);
        $response->assertSee('value="09171112222"', false);
        $response->assertSee('Updated Address');
        $response->assertSee('broker-application-draft:' . $applicant->id . ':' . $opening->id . ':', false);
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
            'bidding_time' => '09:30',
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);
    }
}
