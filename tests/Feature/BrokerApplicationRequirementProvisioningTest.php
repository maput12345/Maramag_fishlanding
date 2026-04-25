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
use Illuminate\Support\Str;
use Illuminate\View\View;
use Tests\TestCase;

class BrokerApplicationRequirementProvisioningTest extends TestCase
{
    use RefreshDatabase;

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

    private function createOpenOpening(): ApplicationOpening
    {
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

        $stall = Stall::create([
            'stall_number' => '12',
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
