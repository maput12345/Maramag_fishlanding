<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Http\Controllers\ApplicationPortalController;
use App\Http\Requests\StoreBrokerApplicationRequest;
use App\Models\ApplicationOpening;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use App\Models\Role;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Tests\TestCase;

class BrokerApplicationRequirementProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_applicant_portal_shows_one_apply_button_for_multiple_open_stalls(): void
    {
        $applicant = $this->createApplicant();
        $this->createOpenOpening('12');
        $this->createOpenOpening('15');

        $response = $this->actingAs($applicant)->get('/applications');

        $response->assertOk();
        $response->assertSee('Stall 12');
        $response->assertSee('Stall 15');
        $response->assertSee('Submit one application for the current vacant stalls.');
        $this->assertSame(1, substr_count($response->getContent(), 'Apply Now'));
    }

    public function test_active_application_hides_apply_button_and_blocks_direct_second_submission(): void
    {
        $applicant = $this->createApplicant();
        $firstOpening = $this->createOpenOpening('12');
        $secondOpening = $this->createOpenOpening('15');

        BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $firstOpening->id,
            'first_name' => 'Existing',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
            'application_status' => 'Submitted',
            'submitted_at' => now(),
        ]);

        $indexResponse = $this->actingAs($applicant)->get('/applications');

        $indexResponse->assertOk();
        $indexResponse->assertSee('You already submitted an application for the current open stalls.');
        $this->assertSame(0, substr_count($indexResponse->getContent(), 'Apply Now'));

        $storeResponse = $this->actingAs($applicant)->post('/applications/openings/' . $secondOpening->getRouteKey(), [
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Second',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000000',
            'requirements' => [],
        ]);

        $storeResponse->assertSessionHasErrors('opening');
        $this->assertSame(1, BrokerApplication::count());
    }

    public function test_applicant_can_resubmit_application_marked_needs_revision(): void
    {
        Storage::fake('public');

        $applicant = $this->createApplicant();
        $opening = $this->createOpenOpening('12');

        $application = BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Needs',
            'last_name' => 'Revision',
            'address' => 'Old Address',
            'contact_number' => '09171234567',
            'application_status' => 'Needs Revision',
            'remarks' => 'Please upload a clearer letter of intent.',
            'submitted_at' => now()->subDay(),
            'review_date' => now(),
        ]);

        $requirementType = RequirementType::create([
            'requirement_name' => 'Letter of Intent',
            'is_required' => true,
        ]);

        $oldPath = 'broker-applications/' . $application->id . '/old-letter.pdf';
        Storage::disk('public')->put($oldPath, 'old file');

        $requirement = $application->requirements()->create([
            'requirement_type_id' => $requirementType->id,
            'file_path' => $oldPath,
            'document_number' => 'OLD-001',
            'verification_status' => 'Rejected',
            'remarks' => 'Document is blurry.',
            'uploaded_at' => now()->subDay(),
        ]);

        $indexResponse = $this->actingAs($applicant)->get('/applications');

        $indexResponse->assertOk();
        $indexResponse->assertSee('Edit Application');

        $editResponse = $this->actingAs($applicant)->get('/applications/' . $application->getRouteKey() . '/edit');

        $editResponse->assertOk();
        $editResponse->assertSee('Please upload a clearer letter of intent.');
        $editResponse->assertSee('Document is blurry.');

        $response = $this->actingAs($applicant)->post('/applications/' . $application->getRouteKey(), [
            '_method' => 'PATCH',
            'first_name' => 'Revised',
            'middle_name' => null,
            'last_name' => 'Applicant',
            'suffix' => null,
            'business_name' => null,
            'address' => 'New Address',
            'contact_number' => '09170000000',
            'requirements' => [
                $requirement->id => [
                    'id' => $requirement->id,
                    'file' => UploadedFile::fake()->create('clear-letter.pdf', 100, 'application/pdf'),
                    'document_number' => 'NEW-001',
                    'issuing_office' => 'LEEO',
                    'issue_date' => now()->toDateString(),
                    'expiry_date' => now()->addYear()->toDateString(),
                ],
            ],
        ]);

        $response->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $requirement->refresh();

        $this->assertSame('Submitted', $application->application_status);
        $this->assertSame('Revised', $application->first_name);
        $this->assertSame('New Address', $application->address);
        $this->assertNull($application->review_date);
        $this->assertSame('Pending', $requirement->verification_status);
        $this->assertNull($requirement->remarks);
        $this->assertSame('NEW-001', $requirement->document_number);
        $this->assertNotSame($oldPath, $requirement->file_path);
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists($requirement->file_path);
    }

    public function test_applicant_cannot_resubmit_application_that_is_not_marked_needs_revision(): void
    {
        $applicant = $this->createApplicant();
        $opening = $this->createOpenOpening('12');

        $application = BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Already',
            'last_name' => 'Submitted',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
            'application_status' => 'Submitted',
            'submitted_at' => now(),
        ]);

        $editResponse = $this->actingAs($applicant)->get('/applications/' . $application->getRouteKey() . '/edit');

        $editResponse->assertRedirect(route('applications.show', $application));
        $editResponse->assertSessionHas('info', 'This application is not currently open for revision.');

        $updateResponse = $this->actingAs($applicant)->patch('/applications/' . $application->getRouteKey(), [
            'first_name' => 'Blocked',
            'last_name' => 'Submitted',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000000',
            'requirements' => [],
        ]);

        $updateResponse->assertForbidden();
        $this->assertSame('Already', $application->fresh()->first_name);
    }

    public function test_application_form_auto_provisions_requirement_types_when_missing(): void
    {
        $applicant = $this->createApplicant();
        $opening = $this->createOpenOpening();

        $this->assertSame(0, RequirementType::count());

        $this->actingAs($applicant);

        $response = app(ApplicationPortalController::class)->create($opening);

        $this->assertInstanceOf(View::class, $response);
        $data = $response->getData();
        $requirementNames = collect($data['requirementTypes'] ?? [])
            ->pluck('requirement_name')
            ->all();

        $this->assertContains('Letter of Intent', $requirementNames);
        $this->assertContains('Tax Clearance', $requirementNames);
        $this->assertContains('Certificate of Incorporation (Corporation) or Partnership', $requirementNames);
        $this->assertContains('Other Documents as May Be Required by the Municipal Market Committee', $requirementNames);
        $this->assertNotContains('Certificate of Incorporation or Partnership', $requirementNames);
        $this->assertNotContains('Other Documents Required by the Municipal Market Committee', $requirementNames);

        $this->assertSame(count(RequirementType::officialChecklistDefinitions()), RequirementType::count());
    }

    public function test_application_submission_stores_uploaded_requirements_even_when_requirement_types_were_missing(): void
    {
        Storage::fake('public');

        $applicant = $this->createApplicant();
        $opening = $this->createOpenOpening();

        $this->assertSame(0, RequirementType::count());

        $this->actingAs($applicant);

        $createResponse = app(ApplicationPortalController::class)->create($opening);

        $this->assertInstanceOf(View::class, $createResponse);

        $requiredDefinitions = collect(RequirementType::officialChecklistDefinitionsFor(RequirementType::APPLICANT_TYPE_NATURAL))
            ->where('is_required', true)
            ->keyBy('requirement_name');

        $requirementTypes = RequirementType::query()
            ->whereIn('requirement_name', $requiredDefinitions->keys()->all())
            ->get();

        $payload = [
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Sample',
            'middle_name' => 'N',
            'last_name' => 'Applicant',
            'suffix' => null,
            'business_name' => null,
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
            'requirements' => [],
        ];

        foreach ($requirementTypes as $requirementType) {
            $payload['requirements'][$requirementType->id] = [
                'file' => UploadedFile::fake()->create(
                    Str::slug($requirementType->requirement_name) . '.pdf',
                    100,
                    'application/pdf'
                ),
            ];
        }

        $request = $this->makeStoreRequest($opening, $payload, $applicant);
        $response = app(ApplicationPortalController::class)->store($request, $opening);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('applications.index'), $response->getTargetUrl());

        $application = BrokerApplication::first();

        $this->assertNotNull($application);
        $this->assertSame($requiredDefinitions->count(), $application->requirements()->count());

        foreach ($application->requirements as $requirement) {
            $this->assertNotSame('', $requirement->file_path);
            Storage::disk('public')->assertExists($requirement->file_path);
        }
    }

    private function createApplicant(): User
    {
        $user = User::create([
            'email' => 'applicant@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $applicantRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::APPLICANT,
        ]);

        $user->roles()->syncWithoutDetaching([$applicantRole->id]);

        return $user;
    }

    private function createOpenOpening(string $stallNumber = '12'): ApplicationOpening
    {
        $admin = User::createUserWithRole(
            [
                'email' => 'leeo-admin-' . Str::random(8) . '@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Admin',
            ]
        );

        $stall = Stall::create([
            'stall_number' => $stallNumber,
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

    private function makeStoreRequest(ApplicationOpening $opening, array $payload, User $applicant): StoreBrokerApplicationRequest
    {
        $request = StoreBrokerApplicationRequest::create(
            '/applications/openings/' . $opening->getRouteKey(),
            'POST',
            $payload,
            [],
            [
                'requirements' => collect($payload['requirements'])
                    ->map(fn (array $requirementPayload) => ['file' => $requirementPayload['file']])
                    ->all(),
            ]
        );

        $router = $this->app->make('router');
        $route = $router->getRoutes()->match($request);

        $router->substituteBindings($route);
        $router->substituteImplicitBindings($route);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $applicant);
        $request->validateResolved();

        return $request;
    }
}
