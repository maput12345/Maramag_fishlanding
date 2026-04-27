<?php

namespace App\Http\Controllers\Admin;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Broker;
use App\Models\BrokerApplication;
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

        if (!in_array($tab, ['admins', 'brokers', 'applicants'], true)) {
            $tab = 'admins';
        }

        if (!in_array($status, ['all', UserStatusConstant::ACTIVE, UserStatusConstant::DEACTIVATED], true)) {
            $status = 'all';
        }

        if (!in_array($role, array_merge(['all'], $adminRoles), true)) {
            $role = 'all';
        }

        $adminsQuery = User::query()
            ->select(['id', 'email', 'status', 'created_at'])
            ->with([
                'roles:id,role_name',
                'employee:id,user_id,first_name,middle_name,last_name,position,contact_number',
            ])
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

        $admins = collect();
        $applicantsQuery = User::query()
            ->select(['id', 'email', 'status', 'created_at'])
            ->with([
                'roles:id,role_name',
                'brokerApplications' => function ($applicationQuery) {
                    $applicationQuery
                        ->select([
                            'id',
                            'user_id',
                            'application_opening_id',
                            'selected_stall_id',
                            'first_name',
                            'middle_name',
                            'last_name',
                            'suffix',
                            'application_status',
                            'submitted_at',
                            'selected_at',
                        ])
                        ->with([
                            'applicationOpening:id,stall_id',
                            'applicationOpening.stall:id,stall_number',
                            'selectedStall:id,stall_number',
                        ])
                        ->latest('submitted_at');
                },
            ])
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
            })
            ->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->whereIn('role_name', [
                    RoleStatusConstant::ADMIN,
                    RoleStatusConstant::STAFF,
                    RoleStatusConstant::BROKER,
                ]);
            });

        if ($status === UserStatusConstant::ACTIVE) {
            $applicantsQuery->active();
        } elseif ($status === UserStatusConstant::DEACTIVATED) {
            $applicantsQuery->deactivated();
        }

        if ($search !== '') {
            $applicantsQuery->where(function ($query) use ($search) {
                $query->where('email', 'like', '%' . $search . '%')
                    ->orWhereHas('brokerApplications', function ($applicationQuery) use ($search) {
                        $applicationQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('middle_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('application_status', 'like', '%' . $search . '%')
                            ->orWhere('contact_number', 'like', '%' . $search . '%');
                    });
            });
        }

        $brokersQuery = Broker::query()
            ->select([
                'id',
                'user_id',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'business_name',
                'address',
                'contact_number',
                'stall_name',
                'created_at',
            ])
            ->with('user:id,email,status');

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

        $brokers = collect();
        $applicants = collect();

        if ($tab === 'admins') {
            $admins = $adminsQuery->get();
        } elseif ($tab === 'brokers') {
            $brokers = $brokersQuery->get();
        } else {
            $applicants = $applicantsQuery->get();
        }

        // Get broker statistics using grouped counts
        $brokerStatusCounts = Broker::query()
            ->join('users', 'users.id', '=', 'brokers.user_id')
            ->selectRaw('users.status, COUNT(*) as total')
            ->groupBy('users.status')
            ->pluck('total', 'users.status');
        $deletedBrokers = Broker::onlyTrashed()->count();
        $activeBrokers = (int) ($brokerStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $deactivatedBrokers = (int) ($brokerStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);
        $totalBrokers = $activeBrokers + $deactivatedBrokers;

        // Get admin statistics using grouped counts
        $employeeQuery = User::query()->whereHas('roles', function ($roleQuery) use ($adminRoles) {
            $roleQuery->whereIn('role_name', $adminRoles);
        });
        $adminStatusCounts = (clone $employeeQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $activeAdmins = (int) ($adminStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $deactivatedAdmins = (int) ($adminStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);
        $totalAdmins = $activeAdmins + $deactivatedAdmins;

        $applicantAccountQuery = User::query()
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
            })
            ->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->whereIn('role_name', [
                    RoleStatusConstant::ADMIN,
                    RoleStatusConstant::STAFF,
                    RoleStatusConstant::BROKER,
                ]);
            });

        $applicantStatusCounts = (clone $applicantAccountQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $activeApplicants = (int) ($applicantStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $archivedApplicants = (int) ($applicantStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);
        $totalApplicants = $activeApplicants + $archivedApplicants;
        $notSelectedApplications = BrokerApplication::query()
            ->where('application_status', 'Not Selected')
            ->whereHas('user.roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
            })
            ->count();

        $count = [
            'deletedBrokers' => $deletedBrokers,
            'deactivatedBrokers' => $deactivatedBrokers,
            'activeBrokers' => $activeBrokers,
            'totalBrokers' => $totalBrokers,
            'deactivatedAdmins' => $deactivatedAdmins,
            'activeAdmins' => $activeAdmins,
            'totalAdmins' => $totalAdmins,
            'activeApplicants' => $activeApplicants,
            'archivedApplicants' => $archivedApplicants,
            'totalApplicants' => $totalApplicants,
            'notSelectedApplications' => $notSelectedApplications,
        ];

        return view('admin.users.index', compact('admins', 'brokers', 'applicants', 'count', 'tab', 'search', 'status', 'role'));
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
        $user = $this->getManagedUser((int) $id);
        $profile = $user->getProfile();

        return view('admin.users._form', [
            'action' => route('admin.users.update', $id),
            'user' => $user,
            'profile' => $profile,
            'title' => 'Edit User',
            'description' => 'Update user information and profile details.'
        ]);
    }

    /**
     * Start an admin-only broker view session.
     */
    public function startBrokerView(Broker $broker): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can switch into broker view.');

        if (!$broker->user || $broker->user->status !== UserStatusConstant::ACTIVE) {
            return redirect()
                ->route('admin.users.index', ['tab' => 'brokers'])
                ->with('error', 'Only active broker accounts can be opened in broker view.');
        }

        Broker::startAdminImpersonation(
            $broker,
            route('admin.users.index', ['tab' => 'brokers'])
        );

        return redirect()
            ->route('broker.dashboard')
            ->with('success', "Now viewing broker workspace for {$broker->name}.");
    }

    /**
     * Exit the current admin broker view session.
     */
    public function stopBrokerView(): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can exit broker view.');

        $brokerName = Broker::getImpersonatedBrokerForAdmin(Auth::user())?->name;
        $returnUrl = Broker::getAdminImpersonationReturnUrl()
            ?: route('admin.users.index', ['tab' => 'brokers']);

        Broker::stopAdminImpersonation();

        return redirect($returnUrl)
            ->with('success', $brokerName
                ? "Exited broker view for {$brokerName}."
                : 'Exited broker view successfully.');
    }

    /**
     * Enable write actions while an admin is inside broker view.
     */
    public function enableBrokerSupportActions(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can enable broker support actions.');

        $broker = Broker::getImpersonatedBrokerForAdmin(Auth::user());

        if (!$broker) {
            return redirect()
                ->route('admin.users.index', ['tab' => 'brokers'])
                ->with('error', 'Enter broker view first before enabling support actions.');
        }

        Broker::enableAdminBrokerSupportActions();

        $redirectTarget = $request->headers->get('referer') ?: route('broker.dashboard');

        return redirect($redirectTarget)
            ->with('warning', "Support Actions are now enabled for {$broker->name}. Changes here will affect the broker's records.");
    }

    /**
     * Return the current admin broker view back to read-only mode.
     */
    public function disableBrokerSupportActions(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only administrators can disable broker support actions.');

        $broker = Broker::getImpersonatedBrokerForAdmin(Auth::user());

        if (!$broker) {
            return redirect()
                ->route('admin.users.index', ['tab' => 'brokers'])
                ->with('error', 'Enter broker view first before changing support actions.');
        }

        Broker::disableAdminBrokerSupportActions();

        $redirectTarget = $request->headers->get('referer') ?: route('broker.dashboard');

        return redirect($redirectTarget)
            ->with('success', "Broker view for {$broker->name} is read-only again.");
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

            $user = $this->getManagedUser((int) $id);

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

            $redirectUrl = route('admin.users.index', [
                'tab' => $this->resolveUserManagementTab($user),
            ]);

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
            $user = $this->getManagedUser((int) $id);
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

    /**
     * Load the user with the profile relations needed by admin forms and mutations.
     */
    private function getManagedUser(int $id): User
    {
        return User::query()
            ->select(['id', 'email', 'status'])
            ->with([
                'roles:id,role_name',
                'broker:id,user_id,first_name,middle_name,last_name,suffix,business_name,address,stall_name,contact_number',
                'employee:id,user_id,first_name,middle_name,last_name,suffix,position,contact_number',
            ])
            ->findOrFail($id);
    }

    /**
     * Resolve the listing tab to return to after a mutation.
     */
    private function resolveUserManagementTab(User $user): string
    {
        if ($user->hasRole(RoleStatusConstant::BROKER)) {
            return 'brokers';
        }

        if ($user->hasRole(RoleStatusConstant::APPLICANT)) {
            return 'applicants';
        }

        return 'admins';
    }
}
