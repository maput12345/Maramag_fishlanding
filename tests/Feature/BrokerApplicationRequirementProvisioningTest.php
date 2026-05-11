<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Http\Controllers\ApplicationPortalController;
use App\Http\Requests\StoreBrokerApplicationRequest;
use App\Models\ApplicationOpening;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
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
        $indexResponse->assertSee('Your application is Submitted. Please track the details under My Applications.');
        $indexResponse->assertSee('View My Application');
        $indexResponse->assertDontSee('Stall 15');
        $this->assertSame(0, substr_count($indexResponse->getContent(), 'Apply Now'));

        $storeResponse = $this->actingAs($applicant)->post('/applications/openings/' . $secondOpening->getRouteKey(), [
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Second',
            'last_name' => 'Applicant',
            'civil_status' => 'Single',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000000',
            'requirements' => [],
        ]);

        $storeResponse->assertSessionHasErrors('opening');
        $this->assertSame(1, BrokerApplication::count());
    }

    public function test_duplicate_submission_to_same_opening_returns_validation_error(): void
    {
        $applicant = $this->createApplicant();
        $opening = $this->createOpenOpening('12');

        BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Existing',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
            'application_status' => 'Rejected',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($applicant)->post('/applications/openings/' . $opening->getRouteKey(), [
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Second',
            'last_name' => 'Applicant',
            'civil_status' => 'Single',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000000',
            'requirements' => [],
        ]);

        $response->assertSessionHasErrors([
            'opening' => 'You already submitted an application for this stall opening.',
        ]);

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

        $indexResponse = $this->actingAs($applicant)->get('/applications/my-applications');

        $indexResponse->assertOk();
        $indexResponse->assertSee('Edit Application');

        $editResponse = $this->actingAs($applicant)->get('/applications/' . $application->getRouteKey() . '/edit');

        $editResponse->assertOk();
        $editResponse->assertSee('Please upload a clearer letter of intent.');
        $editResponse->assertSee('Document is blurry.');

        $response = $this->actingAs($applicant)->post('/applications/' . $application->getRouteKey(), [
            '_method' => 'PATCH',
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
        $this->assertSame('Needs', $application->first_name);
        $this->assertSame('Revision', $application->last_name);
        $this->assertSame('New Address', $application->address);
        $this->assertNotNull($application->revision_resubmitted_at);
        $this->assertSame(1, $application->revision_count);
        $this->assertNull($application->review_date);
        $this->assertSame('Pending', $requirement->verification_status);
        $this->assertNull($requirement->remarks);
        $this->assertSame('NEW-001', $requirement->document_number);
        $this->assertNotSame($oldPath, $requirement->file_path);
        $this->assertTrue($requirement->uploaded_at->equalTo($application->revision_resubmitted_at));
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists($requirement->file_path);

        $admin = User::createUserWithRole(
            [
                'email' => 'review-admin-' . Str::random(8) . '@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Review',
                'last_name' => 'Admin',
            ]
        );

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('New revision file')
            ->assertSee('Updated File');
    }

    public function test_applicant_cannot_resubmit_revision_without_replacement_file(): void
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

        $response = $this->actingAs($applicant)->post('/applications/' . $application->getRouteKey(), [
            '_method' => 'PATCH',
            'business_name' => null,
            'address' => 'New Address',
            'contact_number' => '09170000000',
            'requirements' => [
                $requirement->id => [
                    'id' => $requirement->id,
                    'document_number' => 'NEW-001',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('requirements');

        $application->refresh();
        $requirement->refresh();

        $this->assertSame('Needs Revision', $application->application_status);
        $this->assertNull($application->revision_resubmitted_at);
        $this->assertSame(0, $application->revision_count);
        $this->assertSame($oldPath, $requirement->file_path);
        $this->assertSame('Rejected', $requirement->verification_status);
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
            'civil_status' => 'Single',
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

    public function test_natural_person_validation_does_not_require_juridical_fields(): void
    {
        $validator = $this->validatorForApplicationPayload([
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Natural',
            'last_name' => 'Applicant',
            'civil_status' => 'Single',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
        ]);

        $this->assertFalse($validator->fails());
        $this->assertFalse($validator->errors()->has('business_name'));
        $this->assertFalse($validator->errors()->has('business_address'));
        $this->assertFalse($validator->errors()->has('representative_name'));
        $this->assertFalse($validator->errors()->has('representative_position'));
        $this->assertFalse($validator->errors()->has('representative_contact_number'));
    }

    public function test_married_natural_person_requires_spouse_name_but_not_spouse_contact_number(): void
    {
        $validMarriedPayload = $this->validatorForApplicationPayload([
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Married',
            'last_name' => 'Applicant',
            'civil_status' => 'Married',
            'spouse_name' => 'Maria Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
        ]);

        $this->assertFalse($validMarriedPayload->fails());

        $missingSpouseName = $this->validatorForApplicationPayload([
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Married',
            'last_name' => 'Applicant',
            'civil_status' => 'Married',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09171234567',
        ]);

        $this->assertTrue($missingSpouseName->fails());
        $this->assertTrue($missingSpouseName->errors()->has('spouse_name'));
        $this->assertFalse($missingSpouseName->errors()->has('spouse_contact_number'));
    }

    public function test_juridical_person_validation_does_not_require_natural_person_fields(): void
    {
        $validator = $this->validatorForApplicationPayload([
            'applicant_type' => RequirementType::APPLICANT_TYPE_JURIDICAL,
            'business_name' => 'Maramag Fresh Fish Cooperative',
            'business_address' => 'Market Road, Maramag, Bukidnon',
            'representative_name' => 'Rosa Santos',
            'representative_position' => 'General Manager',
            'representative_contact_number' => '09170000022',
        ]);

        $this->assertFalse($validator->fails());
        $this->assertFalse($validator->errors()->has('civil_status'));
        $this->assertFalse($validator->errors()->has('spouse_name'));
        $this->assertFalse($validator->errors()->has('spouse_contact_number'));
        $this->assertFalse($validator->errors()->has('address'));
        $this->assertFalse($validator->errors()->has('contact_number'));
    }

    public function test_juridical_application_requires_and_stores_business_representative_details(): void
    {
        Storage::fake('public');

        $applicant = $this->createApplicant();
        $opening = $this->createOpenOpening('14');

        $this->actingAs($applicant);

        app(ApplicationPortalController::class)->create($opening);

        $requiredDefinitions = collect(RequirementType::officialChecklistDefinitionsFor(RequirementType::APPLICANT_TYPE_JURIDICAL))
            ->where('is_required', true)
            ->keyBy('requirement_name');

        $requirementTypes = RequirementType::query()
            ->whereIn('requirement_name', $requiredDefinitions->keys()->all())
            ->get();

        $payload = [
            'applicant_type' => RequirementType::APPLICANT_TYPE_JURIDICAL,
            'business_name' => 'Maramag Fresh Fish Cooperative',
            'business_address' => 'Market Road, Maramag, Bukidnon',
            'representative_name' => 'Rosa Santos',
            'representative_position' => 'General Manager',
            'contact_number' => '09170000022',
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
        $this->assertSame(RequirementType::APPLICANT_TYPE_JURIDICAL, $application->applicant_type);
        $this->assertSame('Maramag Fresh Fish Cooperative', $application->business_name);
        $this->assertSame('Market Road, Maramag, Bukidnon', $application->business_address);
        $this->assertSame('Rosa Santos', $application->representative_name);
        $this->assertSame('General Manager', $application->representative_position);
        $this->assertSame('Market Road, Maramag, Bukidnon', $application->address);
        $this->assertNull($application->civil_status);
        $this->assertSame($requiredDefinitions->count(), $application->requirements()->count());
    }

    private function createApplicant(): User
    {
        return User::createUserWithRole(
            [
                'email' => 'applicant-' . Str::random(8) . '@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::APPLICANT,
            ],
            [
                'first_name' => 'Portal',
                'last_name' => 'Applicant',
                'contact_number' => '09171234567',
                'address' => 'Maramag, Bukidnon',
            ]
        );
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
            'bidding_time' => '09:30',
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

    private function validatorForApplicationPayload(array $payload)
    {
        $request = StoreBrokerApplicationRequest::create(
            '/applications/openings/1',
            'POST',
            $payload
        );

        $request->setContainer($this->app);

        return Validator::make($payload, $request->rules());
    }
}
