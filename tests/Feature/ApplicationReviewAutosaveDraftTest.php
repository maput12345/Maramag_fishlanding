<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Models\ApplicationOpening;
use App\Models\ApplicationRequirement;
use App\Models\BrokerApplication;
use App\Models\BrokerApplicationReviewDraft;
use App\Models\RequirementType;
use App\Models\Role;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApplicationReviewAutosaveDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_leeo_review_autosave_stores_draft_without_changing_official_review(): void
    {
        [$admin, $application, $requirement] = $this->createReviewFixture();

        $response = $this->actingAs($admin)->patchJson(
            route('admin.applications.review-draft', $application),
            [
                'application_status' => 'Needs Revision',
                'remarks' => 'Please correct the submitted files.',
                'requirements' => [
                    [
                        'id' => $requirement->id,
                        'verification_status' => 'Rejected',
                        'remarks' => 'Document is not readable.',
                    ],
                ],
            ]
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Draft saved.');

        $draft = BrokerApplicationReviewDraft::query()->first();

        $this->assertNotNull($draft);
        $this->assertSame($application->id, $draft->broker_application_id);
        $this->assertSame($admin->employee->id, $draft->employee_id);
        $this->assertSame('Needs Revision', $draft->draft_payload['application_status']);
        $this->assertSame('Please correct the submitted files.', $draft->draft_payload['remarks']);
        $this->assertSame('Rejected', $draft->draft_payload['requirements'][0]['verification_status']);
        $this->assertSame('Document is not readable.', $draft->draft_payload['requirements'][0]['remarks']);

        $application->refresh();
        $requirement->refresh();

        $this->assertSame('Under Review', $application->application_status);
        $this->assertNull($application->remarks);
        $this->assertSame('Pending', $requirement->verification_status);
        $this->assertNull($requirement->remarks);
    }

    public function test_successful_official_review_clears_autosaved_draft(): void
    {
        [$admin, $application, $requirement] = $this->createReviewFixture();

        BrokerApplicationReviewDraft::create([
            'broker_application_id' => $application->id,
            'employee_id' => $admin->employee->id,
            'draft_payload' => [
                'application_status' => 'Needs Revision',
                'remarks' => 'Draft remarks',
                'requirements' => [
                    [
                        'id' => $requirement->id,
                        'verification_status' => 'Rejected',
                        'remarks' => 'Draft requirement note',
                    ],
                ],
            ],
            'last_saved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.applications.review', $application),
            [
                'application_status' => 'Needs Revision',
                'remarks' => 'Official revision request.',
                'requirements' => [
                    [
                        'id' => $requirement->id,
                        'verification_status' => 'Rejected',
                        'remarks' => 'Please upload a clearer copy.',
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('admin.applications.show', $application));

        $this->assertDatabaseMissing('ApplicationReviewDraft', [
            'broker_application_id' => $application->id,
        ]);
        $this->assertSame('Needs Revision', $application->fresh()->application_status);
        $this->assertSame('Please upload a clearer copy.', $requirement->fresh()->remarks);
    }

    private function createReviewFixture(): array
    {
        $admin = User::createUserWithRole(
            [
                'email' => 'review-autosave-admin@example.com',
                'password' => 'password',
                'role' => RoleStatusConstant::ADMIN,
            ],
            [
                'first_name' => 'Leeo',
                'last_name' => 'Reviewer',
            ]
        );

        $applicant = User::create([
            'email' => 'review-autosave-applicant@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $applicantRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::APPLICANT,
        ]);

        $applicant->roles()->syncWithoutDetaching([$applicantRole->id]);

        $stall = Stall::create([
            'stall_number' => '21',
            'stall_status' => 'Open for Application',
        ]);

        $opening = ApplicationOpening::create([
            'stall_id' => $stall->id,
            'opened_by_employee_id' => $admin->employee->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'bidding_date' => now()->addDays(10)->toDateString(),
            'bidding_time' => '09:30',
            'bidding_location' => 'LEEO Office',
            'opening_status' => 'Open',
        ]);

        $application = BrokerApplication::create([
            'user_id' => $applicant->id,
            'application_opening_id' => $opening->id,
            'first_name' => 'Draft',
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

        return [$admin, $application, $requirement];
    }
}
