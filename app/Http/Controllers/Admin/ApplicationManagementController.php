<?php

namespace App\Http\Controllers\Admin;

use App\Constants\RoleStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBrokerApplicationRequest;
use App\Http\Requests\StoreApplicationOpeningRequest;
use App\Http\Requests\StoreStallRequest;
use App\Mail\BrokerWinnerSelected;
use App\Models\ApplicationOpening;
use App\Models\ApplicationRequirement;
use App\Models\Broker;
use App\Models\BrokerApplication;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Stall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ApplicationManagementController extends Controller
{
    /**
     * Show the LEEO application management workspace.
     */
    public function index(Request $request): View
    {
        $status = $request->get('status');

        $stalls = Stall::orderBy('stall_number')->get();
        $openings = ApplicationOpening::with(['stall', 'openedBy'])
            ->withCount('brokerApplications')
            ->latest()
            ->get();

        $applications = BrokerApplication::with([
            'user',
            'applicationOpening.stall',
            'requirements.requirementType',
            'reviewedBy',
            'selectedBy',
            'broker',
        ])
            ->when($status, function ($query) use ($status) {
                $query->where('application_status', $status);
            })
            ->latest('submitted_at')
            ->paginate(10);

        return view('admin.applications.index', compact('stalls', 'openings', 'applications', 'status'));
    }

    /**
     * Show one application in detail for review.
     */
    public function show(BrokerApplication $application): View
    {
        $application->load([
            'user',
            'applicationOpening.stall',
            'requirements.requirementType',
            'reviewedBy',
            'selectedBy',
            'broker.stall',
        ]);

        return view('admin.applications.show', compact('application'));
    }

    /**
     * Create a new market stall.
     */
    public function storeStall(StoreStallRequest $request): RedirectResponse
    {
        Stall::create([
            'stall_number' => $request->input('stall_number'),
            'stall_status' => 'Vacant',
            'remarks' => $request->input('remarks'),
        ]);

        return redirect()->route('admin.applications.index')
            ->with('success', 'Stall created successfully.');
    }

    /**
     * Open a new application window for a vacant stall.
     */
    public function storeOpening(StoreApplicationOpeningRequest $request): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can open applications.');

        $opening = ApplicationOpening::create([
            'stall_id' => $request->input('stall_id'),
            'opened_by_employee_id' => $employee->id,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'opening_status' => 'Open',
        ]);

        $opening->stall()->update(['stall_status' => 'Open for Application']);

        return redirect()->route('admin.applications.index')
            ->with('success', 'Application opening created successfully.');
    }

    /**
     * Update the opening status without removing the record.
     */
    public function updateOpeningStatus(Request $request, ApplicationOpening $opening): RedirectResponse
    {
        $request->validate([
            'opening_status' => ['required', 'in:Open,Closed,Completed,Cancelled'],
        ]);

        $opening->update([
            'opening_status' => $request->input('opening_status'),
        ]);

        if ($opening->opening_status === 'Open') {
            $opening->stall()->update(['stall_status' => 'Open for Application']);
        } elseif ($opening->opening_status === 'Completed') {
            $opening->stall()->update(['stall_status' => 'Occupied']);
        } else {
            $opening->stall()->update(['stall_status' => 'Vacant']);
        }

        return redirect()->route('admin.applications.index')
            ->with('success', 'Application opening updated successfully.');
    }

    /**
     * Save the LEEO review and requirement verification results.
     */
    public function review(ReviewBrokerApplicationRequest $request, BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');

        DB::transaction(function () use ($request, $application, $employee) {
            foreach ($request->input('requirements', []) as $requirementPayload) {
                /** @var ApplicationRequirement $requirement */
                $requirement = $application->requirements->firstWhere('id', (int) $requirementPayload['id']);

                if (!$requirement) {
                    continue;
                }

                $status = $requirementPayload['verification_status'];
                $requirement->update([
                    'verification_status' => $status,
                    'verified_by_employee_id' => $status === 'Pending' ? null : $employee->id,
                    'verification_date' => $status === 'Pending' ? null : now(),
                    'remarks' => $requirementPayload['remarks'] ?? null,
                ]);
            }

            $application->update([
                'application_status' => $request->input('application_status'),
                'reviewed_by_employee_id' => $employee->id,
                'review_date' => now(),
                'remarks' => $request->input('remarks'),
            ]);
        });

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Application review saved successfully.');
    }

    /**
     * Record the offline winner and convert them into the new broker.
     */
    public function selectWinner(BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can select a winner.');
        abort_if($application->application_status !== 'Qualified', 422, 'Only qualified applications can be promoted to winner.');
        abort_if($application->broker()->exists(), 422, 'This application already created a broker profile.');

        DB::transaction(function () use ($application, $employee) {
            $opening = $application->applicationOpening()->with(['stall', 'brokerApplications.user.roles'])->firstOrFail();
            $winnerSelectedAt = now();

            $opening->brokerApplications()
                ->where('id', '!=', $application->id)
                ->whereNotIn('application_status', ['Rejected', 'Winner'])
                ->update([
                    'application_status' => 'Not Selected',
                    'selected_by_employee_id' => $employee->id,
                    'selected_at' => $winnerSelectedAt,
                ]);

            $application->update([
                'application_status' => 'Winner',
                'selected_by_employee_id' => $employee->id,
                'selected_at' => $winnerSelectedAt,
            ]);

            $user = $application->user;
            $brokerRole = Role::firstOrCreate(
                ['role_name' => RoleStatusConstant::BROKER],
                ['description' => 'Fish broker']
            );
            $applicantRole = Role::where('role_name', RoleStatusConstant::APPLICANT)->first();

            $user->roles()->syncWithoutDetaching([$brokerRole->id]);

            if ($applicantRole) {
                $user->roles()->detach($applicantRole->id);
            }

            $broker = Broker::createFromApplication($application);

            $opening->update(['opening_status' => 'Completed']);
            $opening->stall()->update(['stall_status' => 'Occupied']);

            $this->sendWinnerEmail($user->email, $broker, $opening->stall);
        });

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Winner selected successfully and broker account has been activated.');
    }

    /**
     * Resolve the employee profile attached to the logged-in LEEO account.
     */
    private function resolveCurrentEmployee(): ?Employee
    {
        return Auth::user()?->employee;
    }

    /**
     * Send the winner notification email without crashing the management flow.
     */
    private function sendWinnerEmail(string $email, Broker $broker, Stall $stall): void
    {
        try {
            Mail::to($email)->send(new BrokerWinnerSelected($broker, $stall));
        } catch (\Throwable $exception) {
            Log::warning('Unable to send broker winner email.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
