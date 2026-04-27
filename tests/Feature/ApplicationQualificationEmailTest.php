<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Http\Controllers\Admin\ApplicationManagementController;
use App\Http\Requests\ReviewBrokerApplicationRequest;
use App\Http\Requests\UpdateApplicationOpeningRequest;
use App\Mail\BrokerApplicationQualifiedForBidding;
use App\Models\ApplicationOpening;
use App\Models\ApplicationRequirement;
use App\Models\Broker;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use App\Models\Role;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApplicationQualificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_qualified_application_email_is_sent_once_when_application_first_becomes_qualified(): void
    {
        Mail::fake();

        $admin = User::createUserWithRole(
            [
                'email' => 'leeo-admin@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Admin',
            ]
        );

        $applicant = User::create([
            'email' => 'qualified-applicant@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $applicantRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::APPLICANT,
        ]);

        $applicant->roles()->syncWithoutDetaching([$applicantRole->id]);

        $stall = Stall::create([
            'stall_number' => '12',
            'stall_status' => 'Open for Application',
        ]);

        $opening = ApplicationOpening::create([
            'stall_id' => $stall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-10',
            'bidding_date' => '2026-05-12',
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);

        $application = BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Qualified',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
            'application_status' => 'Under Review',
            'submitted_at' => now(),
        ]);

        $requirementType = RequirementType::create([
            'requirement_name' => 'Letter of Intent',
            'is_required' => true,
        ]);

        $requirement = ApplicationRequirement::create([
            'application_id' => $application->id,
            'requirement_type_id' => $requirementType->id,
            'file_path' => 'requirements/letter-of-intent.pdf',
            'verification_status' => 'Pending',
            'uploaded_at' => now(),
        ]);

        $payload = [
            'application_status' => 'Qualified',
            'remarks' => 'Qualified for bidding.',
            'requirements' => [
                [
                    'id' => $requirement->id,
                    'verification_status' => 'Verified',
                    'remarks' => 'Verified during review.',
                ],
            ],
        ];

        $controller = app(ApplicationManagementController::class);

        $this->actingAs($admin);

        $firstResponse = $controller->review(
            $this->makeReviewRequest($application, $payload, $admin),
            $application
        );

        $this->assertInstanceOf(RedirectResponse::class, $firstResponse);
        $this->assertSame(route('admin.applications.show', $application), $firstResponse->getTargetUrl());

        Mail::assertSent(BrokerApplicationQualifiedForBidding::class, function (BrokerApplicationQualifiedForBidding $mail) use ($applicant, $application, $opening, $stall) {
            return $mail->hasTo($applicant->email)
                && $mail->application->id === $application->id
                && $mail->opening->id === $opening->id
                && $mail->stall->id === $stall->id
                && str_contains($mail->render(), 'LEEO Office, Maramag Fish Landing')
                && str_contains($mail->render(), 'May 12, 2026');
        });

        $secondResponse = $controller->review(
            $this->makeReviewRequest($application->fresh(), $payload, $admin),
            $application->fresh()
        );

        $this->assertInstanceOf(RedirectResponse::class, $secondResponse);
        $this->assertSame(route('admin.applications.show', $application), $secondResponse->getTargetUrl());

        Mail::assertSent(BrokerApplicationQualifiedForBidding::class, 1);
    }

    public function test_updating_bidding_schedule_resends_the_email_to_currently_qualified_applicants(): void
    {
        Mail::fake();

        $admin = User::createUserWithRole(
            [
                'email' => 'leeo-schedule-admin@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Scheduler',
            ]
        );

        $stall = Stall::create([
            'stall_number' => '18',
            'stall_status' => 'Open for Application',
        ]);

        $opening = ApplicationOpening::create([
            'stall_id' => $stall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-10',
            'bidding_date' => '2026-05-12',
            'bidding_location' => 'Old Venue',
            'opening_status' => 'Open',
        ]);

        $qualifiedApplicant = $this->createApplicant('qualified-resend@example.com');
        $underReviewApplicant = $this->createApplicant('review-only@example.com');

        BrokerApplication::create([
            'user_id' => $qualifiedApplicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Qualified',
            'last_name' => 'Resend',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000015',
            'application_status' => 'Qualified',
            'submitted_at' => now(),
        ]);

        BrokerApplication::create([
            'user_id' => $underReviewApplicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Under',
            'last_name' => 'Review',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000016',
            'application_status' => 'Under Review',
            'submitted_at' => now(),
        ]);

        $controller = app(ApplicationManagementController::class);

        $this->actingAs($admin);

        $response = $controller->updateOpening(
            $this->makeUpdateOpeningRequest($opening, [
                'bidding_date' => '2026-05-15',
                'bidding_location' => 'LEEO Conference Hall',
            ], $admin),
            $opening
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('admin.stalls.index'), $response->getTargetUrl());

        Mail::assertSent(BrokerApplicationQualifiedForBidding::class, function (BrokerApplicationQualifiedForBidding $mail) use ($qualifiedApplicant) {
            return $mail->hasTo($qualifiedApplicant->email)
                && str_contains($mail->render(), 'LEEO Conference Hall')
                && str_contains($mail->render(), 'May 15, 2026');
        });

        Mail::assertNotSent(BrokerApplicationQualifiedForBidding::class, function (BrokerApplicationQualifiedForBidding $mail) use ($underReviewApplicant) {
            return $mail->hasTo($underReviewApplicant->email);
        });
    }

    public function test_winner_selection_assigns_the_awarded_stall_from_current_openings(): void
    {
        Mail::fake();

        $admin = User::createUserWithRole(
            [
                'email' => 'leeo-winner-admin@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Winner',
            ]
        );

        $firstStall = Stall::create([
            'stall_number' => '1',
            'stall_status' => 'Open for Application',
        ]);

        $awardedStall = Stall::create([
            'stall_number' => '2',
            'stall_status' => 'Open for Application',
        ]);

        $firstOpening = ApplicationOpening::create([
            'stall_id' => $firstStall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'bidding_date' => now()->addDays(3)->toDateString(),
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);

        $awardedOpening = ApplicationOpening::create([
            'stall_id' => $awardedStall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'bidding_date' => now()->addDays(3)->toDateString(),
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);

        $applicant = $this->createApplicant('winner-selected@example.com');
        $application = BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $firstOpening->id,
            'first_name' => 'Winning',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000017',
            'application_status' => 'Qualified',
            'submitted_at' => now(),
        ]);

        $requirementType = RequirementType::create([
            'requirement_name' => 'Letter of Intent',
            'is_required' => true,
        ]);

        ApplicationRequirement::create([
            'application_id' => $application->id,
            'requirement_type_id' => $requirementType->id,
            'file_path' => 'requirements/letter-of-intent.pdf',
            'verification_status' => 'Verified',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/admin/applications/' . $application->getRouteKey() . '/winner', [
            'selected_stall_id' => $awardedStall->id,
        ]);

        $response->assertRedirect(route('admin.applications.show', $application));

        $application->refresh();
        $this->assertSame('Winner', $application->application_status);
        $this->assertSame($awardedStall->id, $application->selected_stall_id);

        $broker = Broker::where('application_id', $application->id)->first();

        $this->assertNotNull($broker);
        $this->assertSame($awardedStall->id, $broker->stall_id);
        $this->assertSame('Occupied', $awardedStall->fresh()->stall_status);
        $this->assertSame('Completed', $awardedOpening->fresh()->opening_status);
        $this->assertSame('Open', $firstOpening->fresh()->opening_status);
        $this->assertSame('Open for Application', $firstStall->fresh()->stall_status);
    }

    private function createApplicant(string $email): User
    {
        $applicant = User::create([
            'email' => $email,
            'password' => 'password',
            'status' => 'active',
        ]);

        $applicantRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::APPLICANT,
        ]);

        $applicant->roles()->syncWithoutDetaching([$applicantRole->id]);

        return $applicant;
    }

    private function makeReviewRequest(BrokerApplication $application, array $payload, User $admin): ReviewBrokerApplicationRequest
    {
        $request = ReviewBrokerApplicationRequest::create(
            '/admin/applications/' . $application->getRouteKey() . '/review',
            'PATCH',
            $payload
        );
        $router = $this->app->make('router');
        $route = $router->getRoutes()->match($request);

        $router->substituteBindings($route);
        $router->substituteImplicitBindings($route);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $admin);
        $request->validateResolved();

        return $request;
    }

    private function makeUpdateOpeningRequest(ApplicationOpening $opening, array $payload, User $admin): UpdateApplicationOpeningRequest
    {
        $request = UpdateApplicationOpeningRequest::create(
            '/admin/stalls/openings/' . $opening->getRouteKey(),
            'PATCH',
            $payload
        );
        $router = $this->app->make('router');
        $route = $router->getRoutes()->match($request);

        $router->substituteBindings($route);
        $router->substituteImplicitBindings($route);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $admin);
        $request->validateResolved();

        return $request;
    }
}
