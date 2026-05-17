<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ApplicationStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Broker;
use App\Models\BrokerApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Limit account management and broker support controls to full admins.
     */
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can manage users.');

            return $next($request);
        });
    }

    /**
     * @return View
     */
    public function index(Request $request): View
    {
        $adminRoles = [RoleStatusConstant::ADMIN, RoleStatusConstant::STAFF];
        $filters = $this->userManagementFilters($request, $adminRoles);
        $tab = $filters['tab'];
        $search = $filters['search'];
        $status = $filters['status'];
        $role = $filters['role'];

        ['admins' => $admins, 'brokers' => $brokers, 'cashiers' => $cashiers, 'applicants' => $applicants]
            = $this->usersForActiveTab($tab, $search, $status, $role, $adminRoles);

        $count = $this->userManagementCounts($adminRoles);
        $brokersForAssignment = $this->getBrokersForAssignment();

        return view('admin.users.index', compact('admins', 'brokers', 'cashiers', 'applicants', 'count', 'tab', 'search', 'status', 'role', 'brokersForAssignment'));
    }

    private function userManagementFilters(Request $request, array $adminRoles): array
    {
        $tab = $request->query('tab', 'admins');
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');
        $role = $request->query('role', 'all');

        return [
            'tab' => in_array($tab, ['admins', 'brokers', 'cashiers', 'applicants'], true) ? $tab : 'admins',
            'search' => $search,
            'status' => in_array($status, ['all', UserStatusConstant::ACTIVE, UserStatusConstant::DEACTIVATED], true) ? $status : 'all',
            'role' => in_array($role, array_merge(['all'], $adminRoles), true) ? $role : 'all',
        ];
    }

    private function usersForActiveTab(string $tab, string $search, string $status, string $role, array $adminRoles): array
    {
        $users = [
            'admins' => collect(),
            'brokers' => collect(),
            'cashiers' => collect(),
            'applicants' => collect(),
        ];

        $users[$tab] = match ($tab) {
            'brokers' => $this->brokerListingQuery($search, $status)->get(),
            'cashiers' => $this->cashierListingQuery($search, $status)->get(),
            'applicants' => $this->applicantListingQuery($search, $status)->get(),
            default => $this->adminListingQuery($search, $status, $role, $adminRoles)->get(),
        };

        return $users;
    }

    private function adminListingQuery(string $search, string $status, string $role, array $adminRoles): Builder
    {
        $query = User::query()
            ->select(['id', 'email', 'status', 'created_at'])
            ->with([
                'roles:id,role_name',
                'employee:id,user_id,first_name,middle_name,last_name,position,contact_number',
            ])
            ->whereHas('roles', function ($roleQuery) use ($adminRoles) {
                $roleQuery->whereIn('role_name', $adminRoles);
            });

        $this->applyUserStatusFilter($query, $status);

        if (in_array($role, $adminRoles, true)) {
            $query->whereHas('roles', function ($roleQuery) use ($role) {
                $roleQuery->where('role_name', $role);
            });
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('email', 'like', '%' . $search . '%')
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

        return $query;
    }

    private function brokerListingQuery(string $search, string $status): Builder
    {
        $query = Broker::query()
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
            $query->active();
        } elseif ($status === UserStatusConstant::DEACTIVATED) {
            $query->deactivated();
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
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

        return $query;
    }

    private function cashierListingQuery(string $search, string $status): Builder
    {
        $query = User::query()
            ->select(['id', 'email', 'status', 'created_at'])
            ->with([
                'roles:id,role_name',
                'employee:id,user_id,first_name,middle_name,last_name,position,contact_number',
                'brokerStaff:id,user_id,broker_id,position,status',
                'brokerStaff.broker:id,user_id,first_name,middle_name,last_name,suffix,business_name,stall_name',
            ])
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::CASHIER);
            });

        $this->applyUserStatusFilter($query, $status);

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('email', 'like', '%' . $search . '%')
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('middle_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('position', 'like', '%' . $search . '%')
                            ->orWhere('contact_number', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('brokerStaff.broker', function ($brokerQuery) use ($search) {
                        $brokerQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('middle_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('stall_name', 'like', '%' . $search . '%')
                            ->orWhere('business_name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    private function applicantListingQuery(string $search, string $status): Builder
    {
        $query = $this->applicantAccountQuery()
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
            ]);

        $this->applyUserStatusFilter($query, $status);

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('email', 'like', '%' . $search . '%')
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

        return $query;
    }

    private function applyUserStatusFilter(Builder $query, string $status): void
    {
        if ($status === UserStatusConstant::ACTIVE) {
            $query->active();
        } elseif ($status === UserStatusConstant::DEACTIVATED) {
            $query->deactivated();
        }
    }

    private function userManagementCounts(array $adminRoles): array
    {
        $brokerStatusCounts = Broker::query()
            ->join('User', 'User.id', '=', 'Broker.user_id')
            ->selectRaw('User.status, COUNT(*) as total')
            ->groupBy('User.status')
            ->pluck('total', 'User.status');
        $activeBrokers = (int) ($brokerStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $deactivatedBrokers = (int) ($brokerStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);

        $adminStatusCounts = User::query()
            ->whereHas('roles', function ($roleQuery) use ($adminRoles) {
                $roleQuery->whereIn('role_name', $adminRoles);
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $activeAdmins = (int) ($adminStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $deactivatedAdmins = (int) ($adminStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);

        $cashierStatusCounts = User::query()
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::CASHIER);
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $activeCashiers = (int) ($cashierStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $deactivatedCashiers = (int) ($cashierStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);

        $applicantStatusCounts = (clone $this->applicantAccountQuery())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $activeApplicants = (int) ($applicantStatusCounts[UserStatusConstant::ACTIVE] ?? 0);
        $archivedApplicants = (int) ($applicantStatusCounts[UserStatusConstant::DEACTIVATED] ?? 0);

        return [
            'deletedBrokers' => Broker::onlyTrashed()->count(),
            'deactivatedBrokers' => $deactivatedBrokers,
            'activeBrokers' => $activeBrokers,
            'totalBrokers' => $activeBrokers + $deactivatedBrokers,
            'deactivatedAdmins' => $deactivatedAdmins,
            'activeAdmins' => $activeAdmins,
            'totalAdmins' => $activeAdmins + $deactivatedAdmins,
            'deactivatedCashiers' => $deactivatedCashiers,
            'activeCashiers' => $activeCashiers,
            'totalCashiers' => $activeCashiers + $deactivatedCashiers,
            'activeApplicants' => $activeApplicants,
            'archivedApplicants' => $archivedApplicants,
            'totalApplicants' => $activeApplicants + $archivedApplicants,
            'notSelectedApplications' => BrokerApplication::query()
                ->where('application_status', ApplicationStatusConstant::NOT_SELECTED)
                ->whereHas('user.roles', function ($roleQuery) {
                    $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
                })
                ->count(),
        ];
    }

    private function applicantAccountQuery(): Builder
    {
        return User::query()
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
            'description' => 'Add a new admin, staff member, broker, or cashier to the system.',
            'brokersForAssignment' => $this->getBrokersForAssignment(),
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
                'broker_id' => $request->broker_id,
            ];

            User::createUserWithRole($userData, $profileData);

            DB::commit();

            $redirectUrl = route('admin.users.index', [
                'tab' => match ($request->role) {
                    RoleStatusConstant::BROKER => 'brokers',
                    RoleStatusConstant::CASHIER => 'cashiers',
                    default => 'admins',
                },
            ]);

            return redirect($redirectUrl)
                ->with('success', ucfirst($request->role) . ' created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create managed user.', [
                'admin_user_id' => Auth::id(),
                'requested_role' => $request->input('role'),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

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
            if ($request->boolean('change_password') && $request->filled('password')) {
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
            Log::error('Failed to update managed user.', [
                'admin_user_id' => Auth::id(),
                'target_user_id' => $id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

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
            Log::error('Failed to activate managed user.', [
                'admin_user_id' => Auth::id(),
                'target_user_id' => $id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

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
            Log::error('Failed to deactivate managed user.', [
                'admin_user_id' => Auth::id(),
                'target_user_id' => $id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

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
            Log::error('Failed to delete managed user.', [
                'admin_user_id' => Auth::id(),
                'target_user_id' => $id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

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
                'brokerStaff:id,user_id,broker_id,position,status',
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

        if ($user->hasRole(RoleStatusConstant::CASHIER)) {
            return 'cashiers';
        }

        return 'admins';
    }

    private function getBrokersForAssignment()
    {
        return Broker::query()
            ->active()
            ->with('stall:id,stall_number')
            ->orderBy('stall_name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'stall_id', 'stall_name', 'business_name']);
    }
}
