<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Models\ApplicationOpening;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationOpeningRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_leeo_can_add_requirement_and_attach_selected_requirements_to_vacancy(): void
    {
        $admin = $this->createAdmin();
        $stall = Stall::create([
            'stall_number' => 'C-1',
            'stall_status' => 'Vacant',
        ]);

        $requirementResponse = $this->actingAs($admin)->post('/admin/stalls/requirements', [
            'requirement_name' => 'Police Clearance',
            'description' => 'Upload one valid police clearance.',
            'audience' => RequirementType::APPLICANT_TYPE_BOTH,
            'is_required' => '1',
        ]);

        $requirementResponse->assertRedirect(route('admin.stalls.index'));

        $requirement = RequirementType::where('requirement_name', 'Police Clearance')->first();

        $this->assertNotNull($requirement);

        $openingResponse = $this->actingAs($admin)->post('/admin/stalls/openings', [
            'stall_id' => $stall->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'bidding_date' => now()->addDays(3)->toDateString(),
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'requirement_type_ids' => [$requirement->id],
        ]);

        $openingResponse->assertRedirect(route('admin.stalls.index'));

        $opening = ApplicationOpening::first();

        $this->assertNotNull($opening);
        $this->assertDatabaseHas('OpeningRequirement', [
            'application_opening_id' => $opening->id,
            'requirement_type_id' => $requirement->id,
            'is_required' => true,
            'audience' => RequirementType::APPLICANT_TYPE_BOTH,
        ]);
    }

    public function test_applicant_form_and_validation_use_the_opening_requirement_snapshot(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $stall = Stall::create([
            'stall_number' => 'C-2',
            'stall_status' => 'Open for Application',
        ]);
        $selectedRequirement = RequirementType::create([
            'requirement_name' => 'Police Clearance',
            'description' => 'Upload one valid police clearance.',
            'audience' => RequirementType::APPLICANT_TYPE_BOTH,
            'is_required' => true,
            'sort_order' => 10,
        ]);
        $unselectedRequirement = RequirementType::create([
            'requirement_name' => 'Barangay ID Photo',
            'description' => 'Not selected for this vacancy.',
            'audience' => RequirementType::APPLICANT_TYPE_BOTH,
            'is_required' => true,
            'sort_order' => 20,
        ]);

        $opening = ApplicationOpening::create([
            'stall_id' => $stall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'bidding_date' => now()->addDays(3)->toDateString(),
            'bidding_location' => 'LEEO Office, Maramag Fish Landing',
            'opening_status' => 'Open',
        ]);
        $opening->requirementTypes()->sync([
            $selectedRequirement->id => [
                'is_required' => true,
                'audience' => RequirementType::APPLICANT_TYPE_BOTH,
                'sort_order' => 10,
            ],
        ]);

        $applicant = $this->createApplicant();

        $createResponse = $this->actingAs($applicant)->get('/applications/openings/' . $opening->getRouteKey());

        $createResponse->assertOk();
        $createResponse->assertSee('Police Clearance');
        $createResponse->assertDontSee('Barangay ID Photo');

        $missingFileResponse = $this->actingAs($applicant)->post('/applications/openings/' . $opening->getRouteKey(), [
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Snapshot',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000001',
            'requirements' => [],
        ]);

        $missingFileResponse->assertSessionHasErrors('requirements.' . $selectedRequirement->id . '.file');

        $submitResponse = $this->actingAs($applicant)->post('/applications/openings/' . $opening->getRouteKey(), [
            'applicant_type' => RequirementType::APPLICANT_TYPE_NATURAL,
            'first_name' => 'Snapshot',
            'last_name' => 'Applicant',
            'address' => 'Maramag, Bukidnon',
            'contact_number' => '09170000001',
            'requirements' => [
                $selectedRequirement->id => [
                    'file' => UploadedFile::fake()->create('police-clearance.pdf', 100, 'application/pdf'),
                ],
            ],
        ]);

        $submitResponse->assertRedirect(route('applications.index'));

        $application = BrokerApplication::first();

        $this->assertNotNull($application);
        $this->assertSame(1, $application->requirements()->count());
        $this->assertDatabaseHas('SubmittedRequirement', [
            'application_id' => $application->id,
            'requirement_type_id' => $selectedRequirement->id,
        ]);
        $this->assertDatabaseMissing('SubmittedRequirement', [
            'application_id' => $application->id,
            'requirement_type_id' => $unselectedRequirement->id,
        ]);
    }

    private function createAdmin(): User
    {
        return User::createUserWithRole(
            [
                'email' => 'opening-requirement-admin-' . Str::random(8) . '@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Admin',
            ]
        );
    }

    private function createApplicant(): User
    {
        return User::createUserWithRole([
            'email' => 'opening-requirement-applicant-' . Str::random(8) . '@example.com',
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ]);
    }
}
