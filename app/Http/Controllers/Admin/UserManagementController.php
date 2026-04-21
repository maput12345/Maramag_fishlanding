<?php

namespace App\Http\Controllers\Admin;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Broker;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * @return View
     */
    public function index(Request $request): View
    {
        $adminRoles = [RoleStatusConstant::ADMIN, RoleStatusConstant::STAFF];
        $tab = $request->query('tab', 'admins');
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');
        $role = $request->query('role', 'all');

        if (!in_array($tab, ['admins', 'brokers'], true)) {
            $tab = 'admins';
        }

        if (!in_array($status, ['all', UserStatusConstant::ACTIVE, UserStatusConstant::DEACTIVATED], true)) {
            $status = 'all';
        }

        if (!in_array($role, array_merge(['all'], $adminRoles), true)) {
            $role = 'all';
        }

        $adminsQuery = User::with(['roles', 'employee'])
            ->whereHas('roles', function ($roleQuery) use ($adminRoles) {
                $roleQuery->whereIn('role_name', $adminRoles);
            });

        if ($status === UserStatusConstant::ACTIVE) {
            $adminsQuery->active();
        } elseif ($status === UserStatusConstant::DEACTIVATED) {
            $adminsQuery->deactivated();
        }

        if (in_array($role, $adminRoles, true)) {
            $adminsQuery->whereHas('roles', function ($roleQuery) use ($role) {
                $roleQuery->where('role_name', $role);
            });
        }

        if ($search !== '') {
            $adminsQuery->where(function ($query) use ($search) {
                $query->where('email', 'like', '%' . $search . '%')
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('middle_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('position', 'like', '%' . $search . '%')
                            ->orWhere('contact_number', 'like', '%' . $search . '%');
                    });
            });
        }

        $admins = $adminsQuery->get();

        $brokersQuery = Broker::with('user.roles');

        if ($status === UserStatusConstant::ACTIVE) {
            $brokersQuery->active();
        } elseif ($status === UserStatusConstant::DEACTIVATED) {
            $brokersQuery->deactivated();
        }

        if ($search !== '') {
            $brokersQuery->where(function ($query) use ($search) {
                $query
                    ->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('stall_name', 'like', '%' . $search . '%')
                    ->orWhere('business_name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%')
                    ->orWhere('contact_number', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $brokers = $brokersQuery->get();

        // Get broker statistics using query scopes
        $deletedBrokers = Broker::onlyTrashed()->count();
        $deactivatedBrokers = Broker::deactivated()->count();
        $activeBrokers = Broker::active()->count();
        $totalBrokers = Broker::count();

        // Get admin statistics using query scopes
        $employeeQuery = User::whereHas('roles', function ($roleQuery) use ($adminRoles) {
            $roleQuery->whereIn('role_name', $adminRoles);
        });
        $deactivatedAdmins = (clone $employeeQuery)->deactivated()->count();
        $activeAdmins = (clone $employeeQuery)->active()->count();
        $totalAdmins = (clone $employeeQuery)->count();

        $count = [
            'deletedBrokers' => $deletedBrokers,
            'deactivatedBrokers' => $deactivatedBrokers,
            'activeBrokers' => $activeBrokers,
            'totalBrokers' => $totalBrokers,
            'deactivatedAdmins' => $deactivatedAdmins,
            'activeAdmins' => $activeAdmins,
            'totalAdmins' => $totalAdmins
        ];

        return view('admin.users.index', compact('admins', 'brokers', 'count', 'tab', 'search', 'status', 'role'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.users._form', [
            'action' => route('admin.users.store'),
            'user' => null,
            'profile' => null,
            'title' => 'Create New User',
            'description' => 'Add a new admin, staff member, or broker to the system.'
        ]);
    }

    /**
     * @param $id
     *
     * @return View
     */
    public function edit($id): View
    {
        $user = User::findOrFail($id);
        $profile = $user->getProfile();

        return view('admin.users._form', [
            'action' => route('admin.users.update', $id),
            'user' => $user,
            'profile' => $profile,
            'title' => 'Edit User',
            'description' => 'Update user information and profile details.'
        ]);
    }

    // ============== CRUD Operations ============== //

    /**
     * @param UserRequest $request
     * @return RedirectResponse
     */
    public function store(UserRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $userData = [
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role,
            ];

            $profileData = [
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'address' => $request->address,
                'stall_name' => $request->stall_name,
                'contact_number' => $request->contact_number,
                'position' => $request->position,
            ];

            User::createUserWithRole($userData, $profileData);

            DB::commit();

            $redirectUrl = route('admin.users.index', [
                'tab' => $request->role === RoleStatusConstant::BROKER ? 'brokers' : 'admins',
            ]);

            return redirect($redirectUrl)
                ->with('success', ucfirst($request->role) . ' created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create user. Please try again.');
        }
    }


    /**
     * @param UserRequest $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(UserRequest $request, $id): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);

            // Update user data
            $userData = [
                'email' => $request->email,
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Update profile data
            $profileData = [
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'address' => $request->address,
                'stall_name' => $request->stall_name,
                'contact_number' => $request->contact_number,
                'position' => $request->position,
            ];

            $user->updateProfile($profileData);

            DB::commit();

            // Redirect to appropriate tab based on user role
            $redirectUrl = route('admin.users.index');
            if ($user->hasRole(RoleStatusConstant::BROKER)) {
                $redirectUrl .= '?tab=brokers';
            } else {
                $redirectUrl .= '?tab=admins';
            }

            return redirect($redirectUrl)
                ->with('success', 'User updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function activate($id): RedirectResponse
    {
        try {
            $user = User::findOrFail($id);

            $user->updateStatus(UserStatusConstant::ACTIVE);

            return redirect()->back()
                ->with('success', 'User activated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to activate user. Please try again.');
        }
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function deactivate($id): RedirectResponse
    {
        try {
            $user = User::findOrFail($id);

            // Prevent admin from deactivating themselves
            if ($user->id === Auth::id()) {
                return redirect()->back()->with('error', 'You cannot deactivate your own account.');
            }
            $user->updateStatus(UserStatusConstant::DEACTIVATED);

            return redirect()->back()
                ->with('success', 'User deactivated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to deactivate user. Please try again.');
        }
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function destroy($id): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($id);
            $user->deleteProfile();
            DB::commit();
            return redirect()->back()
                ->with('success', 'User deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete user. Please try again.');
        }
    }
}
