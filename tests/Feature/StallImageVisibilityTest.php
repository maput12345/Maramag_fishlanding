<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Models\ApplicationOpening;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class StallImageVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_admin_can_create_a_stall_with_multiple_images(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/stalls', [
            'length_meters' => 3.5,
            'width_meters' => 2,
            'address' => 'Near the main gate beside the ice plant.',
            'stall_images' => [
                $this->fakePngImage('stall-b2-front.png'),
                $this->fakePngImage('stall-b2-side.png'),
            ],
        ]);

        $response->assertRedirect(route('admin.stalls.index'));

        $stall = Stall::query()->where('stall_number', '1')->first();

        $this->assertNotNull($stall);
        $this->assertSame('Near the main gate beside the ice plant.', $stall->address);
        $this->assertSame('7.00', $stall->area_sqm);
        $this->assertNotNull($stall->stall_image_path);
        $stall->load('stallImages');
        $this->assertCount(2, $stall->stallImages);
        Storage::disk('public')->assertExists($stall->stall_image_path);
        Storage::disk('public')->assertExists($stall->stallImages[1]->image_path);
    }

    public function test_applicant_portal_shows_stall_image_and_location_details(): void
    {
        Storage::fake('public');

        $stallImagePaths = [
            $this->fakePngImage('stall-a1-front.png')->store('stalls', 'public'),
            $this->fakePngImage('stall-a1-side.png')->store('stalls', 'public'),
        ];

        $opening = $this->createAvailableOpening([
            'stall_number' => 'A-1',
            'address' => 'Front row near the fish weighing area.',
            'length_meters' => 4,
            'width_meters' => 3,
            'area_sqm' => 12,
            'stall_image_path' => $stallImagePaths[0],
            'stall_gallery_paths' => $stallImagePaths,
        ]);

        $applicant = $this->createApplicant();

        $portalResponse = $this->actingAs($applicant)->get('/applications');

        $portalResponse->assertOk();
        $portalResponse->assertSee('Front row near the fish weighing area.');
        $portalResponse->assertSee('View Photos');
        $portalResponse->assertSee('data-stall-gallery-open="' . $opening->id . '"', false);
        $portalResponse->assertSee(asset('storage/' . $stallImagePaths[0]), false);
        $portalResponse->assertSee(asset('storage/' . $stallImagePaths[1]), false);
        $portalResponse->assertDontSee('gallery view');

        $createResponse = $this->actingAs($applicant)->get('/applications/openings/' . $opening->getRouteKey());

        $createResponse->assertOk();
        $createResponse->assertSee(asset('storage/' . $stallImagePaths[0]), false);
        $createResponse->assertSee(asset('storage/' . $stallImagePaths[1]), false);
        $createResponse->assertSee('Front row near the fish weighing area.');
    }

    private function createAdmin(): User
    {
        return User::createUserWithRole(
            [
                'email' => 'stall-admin-' . Str::random(8) . '@example.com',
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
            'email' => 'stall-applicant-' . Str::random(8) . '@example.com',
            'password' => 'password',
            'role' => RoleStatusConstant::APPLICANT,
        ]);
    }

    private function createAvailableOpening(array $stallAttributes = []): ApplicationOpening
    {
        $admin = $this->createAdmin();
        $stallGalleryPaths = $stallAttributes['stall_gallery_paths'] ?? [];

        unset($stallAttributes['stall_gallery_paths']);

        $stall = Stall::create(array_merge([
            'stall_number' => 'A-1',
            'stall_status' => 'Open for Application',
            'remarks' => null,
            'stall_image_path' => null,
        ], $stallAttributes));

        if (!empty($stallGalleryPaths)) {
            $stall->stallImages()->createMany(
                collect($stallGalleryPaths)->values()->map(function (string $imagePath, int $index): array {
                    return [
                        'image_path' => $imagePath,
                        'sort_order' => $index,
                    ];
                })->all()
            );
        }

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

    private function fakePngImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn2d7sAAAAASUVORK5CYII=')
        );
    }
}
