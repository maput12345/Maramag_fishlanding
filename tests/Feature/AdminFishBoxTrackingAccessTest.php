<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\SalesManagementController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminFishBoxTrackingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_is_redirected_away_from_admin_fish_box_tracking(): void
    {
        $staff = $this->createLeeoUser('staff-tracking@example.com', RoleStatusConstant::STAFF);

        $this->actingAs($staff);

        $request = Request::create('/admin/sales/tracking', 'GET');
        $request->setUserResolver(fn (): User => $staff);
        $controller = app(SalesManagementController::class);
        $response = $controller->fishboxTracking($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('admin.dashboard'), $response->getTargetUrl());
        $this->assertSame('Only admin accounts can access fish box tracking.', session('error'));
    }

    public function test_admin_can_access_admin_fish_box_tracking(): void
    {
        $admin = $this->createLeeoUser('admin-tracking@example.com', RoleStatusConstant::ADMIN);

        $this->actingAs($admin);

        $request = Request::create('/admin/sales/tracking', 'GET');
        $request->setUserResolver(fn (): User => $admin);
        $controller = app(SalesManagementController::class);
        $response = $controller->fishboxTracking($request);

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('admin.sales.tracking', $response->getName());
        $this->assertStringContainsString('Fish Box Tracking', $response->render());
    }

    public function test_staff_cannot_access_user_management_routes(): void
    {
        $staff = $this->createLeeoUser('staff-users@example.com', RoleStatusConstant::STAFF);
        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');

        $response = $this->actingAs($staff)->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_staff_dashboard_hides_admin_only_tracking_cards_and_links(): void
    {
        $staff = $this->createLeeoUser('staff-dashboard@example.com', RoleStatusConstant::STAFF);

        $this->actingAs($staff);

        $controller = app(AdminDashboardController::class);
        $response = $controller->index();
        $html = $response->render();

        $this->assertInstanceOf(View::class, $response);
        $this->assertStringNotContainsString('Fish Box Tracking', $html);
        $this->assertStringNotContainsString('Current Missing Boxes', $html);
        $this->assertStringNotContainsString('Currently Returned Boxes', $html);
        $this->assertStringNotContainsString('Open Tracking', $html);
    }

    private function createLeeoUser(string $email, string $roleName): User
    {
        $user = User::create([
            'email' => $email,
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $role = Role::firstOrCreate([
            'role_name' => $roleName,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }
}
