<?php

namespace App\Http\Controllers\Admin;

use App\Constants\RoleStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBrokerApplicationRequest;
use App\Mail\BrokerApplicationQualifiedForBidding;
use App\Http\Requests\StoreApplicationOpeningRequest;
use App\Http\Requests\StoreStallRequest;
use App\Http\Requests\UpdateApplicationOpeningRequest;
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

        $stalls = Stall::query()
            ->select(['id', 'stall_number', 'stall_status', 'remarks'])
            ->orderBy('stall_number')
            ->get();

        $openings = ApplicationOpening::query()
            ->select([
                'id',
                'stall_id',
                'start_date',
                'end_date',
                'bidding_date',
                'bidding_location',
                'opening_status',
                'created_at',
            ])
            ->with(['stall:id,stall_number'])
            ->withCount('brokerApplications')
            ->latest()
            ->get();

        $applications = BrokerApplication::query()
            ->select([
                'id',
                'user_id',
                'application_opening_id',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'application_status',
                'submitted_at',
            ])
            ->with([
                'user:id,email',
                'applicationOpening:id,stall_id',
                'applicationOpening.stall:id,stall_number',
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
            'user:id,email',
            'applicationOpening:id,stall_id,start_date,end_date,bidding_date,bidding_location',
            'applicationOpening.stall:id,stall_number',
            'requirements:id,application_id,requirement_type_id,file_path,document_number,issuing_office,verification_status,remarks,uploaded_at',
            'requirements.requirementType:id,requirement_name',
            'reviewedBy:id,first_name,middle_name,last_name,suffix',
            'selectedBy:id,first_name,middle_name,last_name,suffix',
            'broker:id,application_id,stall_id',
            'broker.stall:id,stall_number',
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
            'bidding_date' => $request->input('bidding_date'),
            'bidding_location' => $request->input('bidding_location'),
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
     * Update the bidding schedule details for an opening.
     */
    public function updateOpening(UpdateApplicationOpeningRequest $request, ApplicationOpening $opening): RedirectResponse
    {
        $originalBiddingDate = optional($opening->bidding_date)->toDateString();
        $originalBiddingLocation = $opening->bidding_location;

        $opening->update([
            'bidding_date' => $request->input('bidding_date'),
            'bidding_location' => $request->input('bidding_location'),
        ]);

        $updatedBiddingDate = optional($opening->fresh()->bidding_date)->toDateString();
        $updatedBiddingLocation = $opening->bidding_location;
        $qualifiedApplicantsNotified = 0;

        if ($originalBiddingDate !== $updatedBiddingDate || $originalBiddingLocation !== $updatedBiddingLocation) {
            $qualifiedApplications = $opening->brokerApplications()
                ->where('application_status', 'Qualified')
                ->with([
                    'user:id,email',
                    'applicationOpening:id,stall_id,start_date,bidding_date,bidding_location',
                    'applicationOpening.stall:id,stall_number',
                ])
                ->get();

            foreach ($qualifiedApplications as $application) {
                $this->sendQualifiedForBiddingEmail($application);
                $qualifiedApplicantsNotified++;
            }
        }

        $message = 'Bidding schedule updated successfully.';

        if ($qualifiedApplicantsNotified > 0) {
            $message .= ' Updated schedule emails were sent to ' . $qualifiedApplicantsNotified . ' qualified applicant'
                . ($qualifiedApplicantsNotified === 1 ? '' : 's') . '.';
        }

        return redirect()->route('admin.applications.index')
            ->with('success', $message);
    }

    /**
     * Save the LEEO review and requirement verification results.
     */
    public function review(ReviewBrokerApplicationRequest $request, BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();
        $shouldSendQualifiedEmail = $application->application_status !== 'Qualified'
            && $request->input('application_status') === 'Qualified';

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');

        DB::transaction(function () use ($request, $application, $employee) {
            $requirements = $application->requirements()
                ->select(['id', 'application_id'])
                ->get()
                ->keyBy('id');

            foreach ($request->input('requirements', []) as $requirementPayload) {
                /** @var ApplicationRequirement $requirement */
                $requirement = $requirements->get((int) $requirementPayload['id']);

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

        if ($shouldSendQualifiedEmail) {
            $application->refresh()->load([
                'user:id,email',
                'applicationOpening:id,stall_id,start_date,bidding_date,bidding_location',
                'applicationOpening.stall:id,stall_number',
            ]);

            $this->sendQualifiedForBiddingEmail($application);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Application review saved successfully.');
    }

    /**
     * Record the offline winner and convert them into the new broker.
     */
    public function selectWinner(BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        if (!$employee) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Only LEEO employee accounts can select a winner.');
        }

        $application->loadMissing([
            'broker:id,application_id',
            'requirements:id,application_id,verification_status',
            'user:id,email',
            'applicationOpening:id,stall_id',
            'applicationOpening.stall:id,stall_number',
        ]);

        if ($application->broker) {
            return redirect()->route('admin.applications.show', $application)
                ->with('info', 'Winner already confirmed for this application.');
        }

        if ($application->application_status !== 'Qualified') {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Only qualified applications can be promoted to winner.');
        }

        if (!$application->canBeQualified()) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'All application requirements must be verified before a winner can be selected.');
        }

        try {
            DB::transaction(function () use ($application, $employee) {
                $opening = $application->applicationOpening;

                if (!$opening) {
                    throw new \RuntimeException('Application opening not found.');
                }

                $opening->loadMissing('stall:id,stall_number');
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
                $roles = Role::query()
                    ->whereIn('role_name', [RoleStatusConstant::BROKER, RoleStatusConstant::APPLICANT])
                    ->get()
                    ->keyBy('role_name');

                $brokerRole = $roles->get(RoleStatusConstant::BROKER)
                    ?? Role::create([
                        'role_name' => RoleStatusConstant::BROKER,
                        'description' => 'Fish broker',
                    ]);
                $applicantRole = $roles->get(RoleStatusConstant::APPLICANT);

                $user->roles()->syncWithoutDetaching([$brokerRole->id]);

                if ($applicantRole) {
                    $user->roles()->detach($applicantRole->id);
                }

                $broker = Broker::createFromApplication($application);

                $opening->update(['opening_status' => 'Completed']);
                $opening->stall?->update(['stall_status' => 'Occupied']);

                $this->sendWinnerEmail($user->email, $broker, $opening->stall);
            });
        } catch (\Throwable $exception) {
            Log::error('Unable to confirm winner for broker application.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Failed to confirm winner. Please try again.');
        }

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

    /**
     * Send the qualified-for-bidding notification email without breaking review flow.
     */
    private function sendQualifiedForBiddingEmail(BrokerApplication $application): void
    {
        $email = $application->user?->email;
        $opening = $application->applicationOpening;
        $stall = $opening?->stall;

        if (!$email || !$opening || !$stall) {
            return;
        }

        try {
            Mail::to($email)->send(new BrokerApplicationQualifiedForBidding($application, $opening, $stall));
        } catch (\Throwable $exception) {
            Log::warning('Unable to send broker qualified-for-bidding email.', [
                'application_id' => $application->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
