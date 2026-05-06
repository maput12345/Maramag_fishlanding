<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Models\ApplicationOpening;
use App\Models\ApplicationRequirement;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use App\Models\Role;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApplicantAccountArchivingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_final_winner_selection_archives_remaining_applicant_only_accounts(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $stall = $this->createOpenStall('1');
        $opening = $this->createOpening($admin, $stall);
        $winnerUser = $this->createApplicant('archive-winner@example.com');
        $nonWinnerUser = $this->createApplicant('archive-non-winner@example.com');
        $winnerApplication = $this->createBrokerApplication($winnerUser, $opening, 'Qualified');
        $nonWinnerApplication = $this->createBrokerApplication($nonWinnerUser, $opening, 'Submitted');

        $this->attachVerifiedRequirement($winnerApplication);

        $response = $this->actingAs($admin)->post(route('admin.applications.winner', $winnerApplication), [
            'selected_stall_id' => $stall->id,
        ]);

        $response->assertRedirect(route('admin.applications.show', $winnerApplication));
        $response->assertSessionHas('success', function (string $message) {
            return str_contains($message, '1 remaining application was marked Not Selected')
                && str_contains($message, '1 applicant-only account was archived/deactivated');
        });

        $this->assertSame('Winner', $winnerApplication->fresh()->application_status);
        $this->assertSame(UserStatusConstant::ACTIVE, $winnerUser->fresh()->status);
        $this->assertTrue($winnerUser->fresh()->isBroker());
        $this->assertSame('Not Selected', $nonWinnerApplication->fresh()->application_status);
        $this->assertSame(UserStatusConstant::DEACTIVATED, $nonWinnerUser->fresh()->status);

        $this->actingAs($winnerUser->fresh())
            ->get(route('applications.index'))
            ->assertRedirect(route('broker.dashboard'))
            ->assertSessionHas('info', 'Your application account has been converted to a broker account.');
    }

    public function test_applicant_accounts_are_not_archived_while_another_stall_is_still_available(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $firstStall = $this->createOpenStall('2');
        $secondStall = $this->createOpenStall('3');
        $firstOpening = $this->createOpening($admin, $firstStall);
        $this->createOpening($admin, $secondStall);
        $winnerUser = $this->createApplicant('partial-winner@example.com');
        $waitingUser = $this->createApplicant('still-waiting@example.com');
        $winnerApplication = $this->createBrokerApplication($winnerUser, $firstOpening, 'Qualified');
        $waitingApplication = $this->createBrokerApplication($waitingUser, $firstOpening, 'Qualified');

        $this->attachVerifiedRequirement($winnerApplication);

        $response = $this->actingAs($admin)->post(route('admin.applications.winner', $winnerApplication), [
            'selected_stall_id' => $firstStall->id,
        ]);

        $response->assertRedirect(route('admin.applications.show', $winnerApplication));

        $this->assertSame('Qualified', $waitingApplication->fresh()->application_status);
        $this->assertSame(UserStatusConstant::ACTIVE, $waitingUser->fresh()->status);
    }

    public function test_user_management_shows_archived_applicant_accounts(): void
    {
        $admin = $this->createAdmin();
        $applicant = $this->createApplicant('archived-list@example.com');
        $applicant->updateStatus(UserStatusConstant::DEACTIVATED);
        $opening = $this->createOpening($admin, $this->createOpenStall('4'));
        $this->createBrokerApplication($applicant, $opening, 'Not Selected');

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'tab' => 'applicants',
            'status' => UserStatusConstant::DEACTIVATED,
        ]));

        $response->assertOk();
        $response->assertSee('Applicant Archive');
        $response->assertSee('archived-list@example.com');
        $response->assertSee('Archived');
        $response->assertSee('Not Selected');
    }

    public function test_archived_applicant_login_gets_clear_application_process_message(): void
    {
        $applicant = $this->createApplicant('archived-login@example.com');
        $applicant->updateStatus(UserStatusConstant::DEACTIVATED);

        $response = $this->post('/login', [
            'email' => 'archived-login@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This applicant account has been archived after the application process. Please create a new account when a new stall application opens.',
        ]);
    }

    private function createAdmin(): User
    {
        return User::createUserWithRole(
            [
                'email' => 'archiving-admin-' . uniqid() . '@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Admin',
            ]
        );
    }

    private function createApplicant(string $email): User
    {
        $applicant = User::create([
            'email' => $email,
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $applicantRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::APPLICANT,
        ]);

        $applicant->roles()->syncWithoutDetaching([$applicantRole->id]);

        return $applicant;
    }

    private function createOpenStall(string $stallNumber): Stall
    {
        return Stall::create([
            'stall_number' => $stallNumber,
            'stall_status' => 'Open for Application',
        ]);
    }

    private function createOpening(User $admin, Stall $stall): ApplicationOpening
    {
        return ApplicationOpening::create([
            'stall_id' => $stall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'bidding_date' => now()->addDays(10)->toDateString(),
            'bidding_time' => '09:30',
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);
    }

    private function createBrokerApplication(User $applicant, ApplicationOpening $opening, string $status): BrokerApplication
    {
        return BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Archive',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
            'application_status' => $status,
            'submitted_at' => now(),
        ]);
    }

    private function attachVerifiedRequirement(BrokerApplication $application): void
    {
        $requirementType = RequirementType::create([
            'requirement_name' => 'Letter of Intent ' . uniqid(),
            'is_required' => true,
        ]);

        ApplicationRequirement::create([
            'application_id' => $application->id,
            'requirement_type_id' => $requirementType->id,
            'file_path' => 'requirements/letter-of-intent.pdf',
            'verification_status' => 'Verified',
            'uploaded_at' => now(),
        ]);
    }
}
