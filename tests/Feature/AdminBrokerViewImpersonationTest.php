<?php

namespace Tests\Feature;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Middleware\BrokerMiddleware;
use App\Models\Broker;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class AdminBrokerViewImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_start_and_stop_broker_view_session(): void
    {
        $admin = $this->createAdminUser();
        $broker = $this->createBroker('broker-view-target@example.com', 'Target', 'Broker', 'Stall 7');

        $this->actingAs($admin);

        $controller = app(UserManagementController::class);

        $startResponse = $controller->startBrokerView($broker);

        $this->assertInstanceOf(RedirectResponse::class, $startResponse);
        $this->assertSame(route('broker.dashboard'), $startResponse->getTargetUrl());
        $this->assertSame($broker->id, session(Broker::ADMIN_IMPERSONATION_SESSION_KEY));
        $this->assertFalse(Broker::areAdminBrokerSupportActionsEnabled($admin));
        $this->assertSame($broker->id, Broker::getBrokerIdByUserId($admin->id));

        $stopResponse = $controller->stopBrokerView();

        $this->assertInstanceOf(RedirectResponse::class, $stopResponse);
        $this->assertSame(route('admin.users.index', ['tab' => 'brokers']), $stopResponse->getTargetUrl());
        $this->assertNull(session(Broker::ADMIN_IMPERSONATION_SESSION_KEY));
        $this->assertNull(session(Broker::ADMIN_SUPPORT_ACTIONS_SESSION_KEY));
    }

    public function test_broker_middleware_redirects_admin_without_context_but_allows_impersonating_admin_and_real_broker(): void
    {
        $admin = $this->createAdminUser();
        $broker = $this->createBroker('broker-dashboard@example.com', 'Normal', 'Broker', 'Stall 8');
        $brokerUser = User::findOrFail($broker->user_id);
        $middleware = new BrokerMiddleware();
        $request = Request::create('/broker/dashboard', 'GET');

        $this->actingAs($admin);
        Broker::stopAdminImpersonation();

        $redirectResponse = $middleware->handle($request, fn () => new Response('allowed'));

        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame(route('admin.users.index', ['tab' => 'brokers']), $redirectResponse->getTargetUrl());

        session([
            Broker::ADMIN_IMPERSONATION_SESSION_KEY => $broker->id,
            Broker::ADMIN_IMPERSONATION_RETURN_URL_SESSION_KEY => route('admin.users.index', ['tab' => 'brokers']),
        ]);

        $adminAllowedResponse = $middleware->handle($request, fn () => new Response('allowed'));

        $this->assertInstanceOf(Response::class, $adminAllowedResponse);
        $this->assertSame('allowed', $adminAllowedResponse->getContent());
        $this->assertSame($broker->id, Broker::getBrokerIdByUserId($admin->id));

        Broker::stopAdminImpersonation();
        $this->actingAs($brokerUser);

        $brokerAllowedResponse = $middleware->handle($request, fn () => new Response('allowed'));

        $this->assertInstanceOf(Response::class, $brokerAllowedResponse);
        $this->assertSame('allowed', $brokerAllowedResponse->getContent());
        $this->assertSame($broker->id, Broker::getBrokerIdByUserId($brokerUser->id));
    }

    public function test_admin_broker_view_is_read_only_until_support_actions_are_enabled(): void
    {
        $admin = $this->createAdminUser();
        $broker = $this->createBroker('broker-readonly@example.com', 'Read', 'Only', 'Stall 9');
        $middleware = new BrokerMiddleware();

        $this->actingAs($admin);

        Broker::startAdminImpersonation($broker, route('admin.users.index', ['tab' => 'brokers']));

        $getRequest = Request::create('/broker/sales', 'GET');
        $getAllowedResponse = $middleware->handle($getRequest, fn () => new Response('allowed'));

        $this->assertInstanceOf(Response::class, $getAllowedResponse);
        $this->assertSame('allowed', $getAllowedResponse->getContent());
        $this->assertTrue(Broker::isAdminBrokerViewReadOnly($admin));

        $postRequest = Request::create('/broker/sales', 'POST');
        $postRequest->headers->set('referer', route('broker.sales.sales'));
        $blockedResponse = $middleware->handle($postRequest, fn () => new Response('allowed'));

        $this->assertInstanceOf(RedirectResponse::class, $blockedResponse);
        $this->assertSame(route('broker.sales.sales'), $blockedResponse->getTargetUrl());

        Broker::enableAdminBrokerSupportActions();

        $postAllowedResponse = $middleware->handle($postRequest, fn () => new Response('allowed'));

        $this->assertInstanceOf(Response::class, $postAllowedResponse);
        $this->assertSame('allowed', $postAllowedResponse->getContent());
        $this->assertFalse(Broker::isAdminBrokerViewReadOnly($admin));

        Broker::disableAdminBrokerSupportActions();

        $this->assertTrue(Broker::isAdminBrokerViewReadOnly($admin));
    }

    private function createAdminUser(): User
    {
        $user = User::create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $adminRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::ADMIN,
        ]);

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        return $user;
    }

    private function createBroker(string $email, string $firstName, string $lastName, string $stallName): Broker
    {
        $user = User::create([
            'email' => $email,
            'password' => 'password',
            'status' => UserStatusConstant::ACTIVE,
        ]);

        $brokerRole = Role::firstOrCreate([
            'role_name' => RoleStatusConstant::BROKER,
        ]);

        $user->roles()->syncWithoutDetaching([$brokerRole->id]);

        return Broker::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'address' => $stallName . ' Address',
            'stall_name' => $stallName,
            'broker_status' => 'Active',
        ]);
    }
}
