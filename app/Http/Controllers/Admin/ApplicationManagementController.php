<?php

namespace App\Http\Controllers\Admin;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBrokerApplicationRequest;
use App\Mail\BrokerApplicationQualifiedForBidding;
use App\Http\Requests\StoreApplicationOpeningRequest;
use App\Http\Requests\StoreRequirementTypeRequest;
use App\Http\Requests\StoreStallRequest;
use App\Http\Requests\UpdateApplicationOpeningRequest;
use App\Mail\BrokerWinnerSelected;
use App\Models\ApplicationOpening;
use App\Models\ApplicationRequirement;
use App\Models\Broker;
use App\Models\BrokerApplication;
use App\Models\BrokerApplicationReviewDraft;
use App\Models\Employee;
use App\Models\RequirementType;
use App\Models\Role;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationManagementController extends Controller
{
    /**
     * Show submitted broker applications for review.
     */
    public function index(Request $request): View
    {
        $status = $request->get('status');

        $applications = BrokerApplication::query()
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
            ])
            ->with([
                'user:id,email',
                'applicationOpening:id,stall_id',
                'applicationOpening.stall:id,stall_number',
                'selectedStall:id,stall_number',
            ])
            ->when($status, function ($query) use ($status) {
                $query->where('application_status', $status);
            })
            ->latest('submitted_at')
            ->paginate(10);

        return view('admin.applications.index', compact('applications', 'status'));
    }

    /**
     * Show the stall management workspace.
     */
    public function stallsIndex(): View
    {
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
            ->with(['stall:id,stall_number,stall_status'])
            ->withCount('brokerApplications')
            ->latest()
            ->get();

        $requirementTypes = RequirementType::selectableChecklistTypes();

        return view('admin.stalls.index', compact('stalls', 'openings', 'requirementTypes'));
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
            'selectedStall:id,stall_number,stall_status',
            'broker:id,application_id,stall_id',
            'broker.stall:id,stall_number',
        ]);

        $availableWinnerStalls = $this->availableWinnerStalls();
        $employee = $this->resolveCurrentEmployee();
        $reviewDraft = $employee
            ? BrokerApplicationReviewDraft::query()
                ->where('broker_application_id', $application->id)
                ->where('employee_id', $employee->id)
                ->first()
            : null;

        return view('admin.applications.show', compact('application', 'availableWinnerStalls', 'reviewDraft'));
    }

    /**
     * Create a new market stall.
     */
    public function storeStall(StoreStallRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $storedImagePaths = collect($request->file('stall_images', []))
            ->filter()
            ->map(fn ($uploadedImage) => $uploadedImage->store('stalls', 'public'))
            ->values();

        DB::transaction(function () use ($validated, $storedImagePaths) {
            $stall = Stall::create([
                'stall_number' => $validated['stall_number'],
                'stall_status' => 'Vacant',
                'remarks' => $validated['remarks'] ?? null,
                'stall_image_path' => $storedImagePaths->first(),
            ]);

            if ($storedImagePaths->isNotEmpty()) {
                $stall->stallImages()->createMany(
                    $storedImagePaths->values()->map(function (string $imagePath, int $index): array {
                        return [
                            'image_path' => $imagePath,
                            'sort_order' => $index,
                        ];
                    })->all()
                );
            }
        });

        return redirect()->route('admin.stalls.index')
            ->with('success', 'Stall created successfully.');
    }

    /**
     * Add a reusable requirement to the LEEO requirement master list.
     */
    public function storeRequirementType(StoreRequirementTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $nextSortOrder = ((int) RequirementType::max('sort_order')) + 10;

        RequirementType::create([
            'requirement_name' => $validated['requirement_name'],
            'description' => $validated['description'] ?? null,
            'audience' => $validated['audience'],
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $nextSortOrder,
        ]);

        return redirect()->route('admin.stalls.index')
            ->with('success', 'Requirement added to the master list.');
    }

    /**
     * Open a new application window for a vacant stall.
     */
    public function storeOpening(StoreApplicationOpeningRequest $request): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can open applications.');

        $validated = $request->validated();
        $selectedRequirementIds = collect($validated['requirement_type_ids'])
            ->map(fn ($requirementId) => (int) $requirementId)
            ->unique()
            ->values();

        DB::transaction(function () use ($validated, $employee, $selectedRequirementIds) {
            $opening = ApplicationOpening::create([
                'stall_id' => $validated['stall_id'],
                'opened_by_employee_id' => $employee->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'bidding_date' => $validated['bidding_date'],
                'bidding_location' => $validated['bidding_location'],
                'opening_status' => 'Open',
            ]);

            $requirementTypes = RequirementType::query()
                ->whereIn('id', $selectedRequirementIds)
                ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('sort_order')
                ->orderBy('requirement_name')
                ->get();

            $opening->requirementTypes()->sync(
                $requirementTypes->mapWithKeys(function (RequirementType $requirementType, int $index) {
                    return [
                        $requirementType->id => [
                            'is_required' => $requirementType->is_required,
                            'audience' => $requirementType->audience ?: RequirementType::APPLICANT_TYPE_BOTH,
                            'sort_order' => $requirementType->sort_order ?: (($index + 1) * 10),
                        ],
                    ];
                })->all()
            );

            $opening->stall()->update(['stall_status' => 'Open for Application']);
        });

        return redirect()->route('admin.stalls.index')
            ->with('success', 'Application opening created successfully.');
    }

    /**
     * Update the opening status without removing the record.
     */
    public function updateOpeningStatus(Request $request, ApplicationOpening $opening): RedirectResponse
    {
        $request->validate([
            'opening_status' => ['required', 'in:Vacant,Occupied,Cancelled'],
        ]);

        $selectedStatus = $request->input('opening_status');

        if ($selectedStatus === 'Occupied') {
            $opening->update(['opening_status' => 'Completed']);
            $opening->stall()->update(['stall_status' => 'Occupied']);
        } elseif ($selectedStatus === 'Cancelled') {
            $opening->update(['opening_status' => 'Cancelled']);
            $opening->stall()->update(['stall_status' => 'Vacant']);
        } else {
            $opening->update(['opening_status' => 'Open']);
            $opening->stall()->update(['stall_status' => 'Open for Application']);
        }

        return redirect()->route('admin.stalls.index')
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

        return redirect()->route('admin.stalls.index')
            ->with('success', $message);
    }

    /**
     * Autosave an in-progress LEEO review without changing official application results.
     */
    public function saveReviewDraft(Request $request, BrokerApplication $application): JsonResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can autosave application reviews.');

        $validated = $request->validate([
            'application_status' => ['nullable', Rule::in(['Under Review', 'Needs Revision', 'Rejected', 'Qualified'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['nullable', 'array'],
            'requirements.*.id' => ['required_with:requirements', 'integer'],
            'requirements.*.verification_status' => ['nullable', Rule::in(['Pending', 'Verified', 'Rejected'])],
            'requirements.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowedRequirementIds = $application->requirements()
            ->pluck('id')
            ->map(fn ($requirementId) => (int) $requirementId);

        $requirements = collect($validated['requirements'] ?? [])
            ->map(function (array $requirementPayload) {
                return [
                    'id' => (int) $requirementPayload['id'],
                    'verification_status' => $requirementPayload['verification_status'] ?? 'Pending',
                    'remarks' => $requirementPayload['remarks'] ?? null,
                ];
            })
            ->filter(fn (array $requirementPayload) => $requirementPayload['id'] > 0)
            ->values();

        if ($requirements->pluck('id')->diff($allowedRequirementIds)->isNotEmpty()) {
            return response()->json([
                'message' => 'One or more requirement review rows do not belong to this application.',
            ], 422);
        }

        $draft = BrokerApplicationReviewDraft::updateOrCreate(
            [
                'broker_application_id' => $application->id,
                'employee_id' => $employee->id,
            ],
            [
                'draft_payload' => [
                    'application_status' => $validated['application_status'] ?? $application->application_status,
                    'remarks' => $validated['remarks'] ?? null,
                    'requirements' => $requirements->all(),
                ],
                'last_saved_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Draft saved.',
            'last_saved_at' => optional($draft->last_saved_at)->toIso8601String(),
            'last_saved_at_label' => optional($draft->last_saved_at)->format('M d, Y h:i A'),
        ]);
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

            BrokerApplicationReviewDraft::query()
                ->where('broker_application_id', $application->id)
                ->delete();
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
    public function selectWinner(Request $request, BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();
        $notSelectedApplicantCount = 0;
        $archivedApplicantAccountCount = 0;

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
            'selectedStall:id,stall_number,stall_status',
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

        $availableStallIds = $this->availableWinnerStalls()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $validated = $request->validate([
            'selected_stall_id' => ['required', Rule::in($availableStallIds)],
        ], [
            'selected_stall_id.required' => 'Please select the stall to award to this applicant.',
            'selected_stall_id.in' => 'The selected stall is no longer available for winner assignment.',
        ]);

        try {
            DB::transaction(function () use ($application, $employee, $validated, &$notSelectedApplicantCount, &$archivedApplicantAccountCount) {
                $opening = $application->applicationOpening;

                if (!$opening) {
                    throw new \RuntimeException('Application opening not found.');
                }

                $opening->loadMissing('stall:id,stall_number');
                $selectedStall = Stall::query()
                    ->whereKey($validated['selected_stall_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$selectedStall || !in_array($selectedStall->stall_status, ApplicationOpening::AVAILABLE_STALL_STATUSES, true)) {
                    throw new \RuntimeException('Selected stall is no longer available.');
                }

                $winnerSelectedAt = now();

                $application->update([
                    'application_status' => 'Winner',
                    'selected_stall_id' => $selectedStall->id,
                    'selected_by_employee_id' => $employee->id,
                    'selected_at' => $winnerSelectedAt,
                ]);

                $application->setRelation('selectedStall', $selectedStall);

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

                ApplicationOpening::query()
                    ->where('stall_id', $selectedStall->id)
                    ->whereIn('opening_status', ['Open', 'Closed'])
                    ->latest('id')
                    ->first()
                    ?->update(['opening_status' => 'Completed']);

                $selectedStall->update(['stall_status' => 'Occupied']);

                if ($this->availableWinnerStalls()->isEmpty()) {
                    $notSelectedApplications = BrokerApplication::query()
                        ->where('id', '!=', $application->id)
                        ->whereNotIn('application_status', ['Rejected', 'Winner', 'Not Selected'])
                        ->get(['id', 'user_id']);

                    $notSelectedApplicantCount = $notSelectedApplications->count();

                    BrokerApplication::query()
                        ->whereKey($notSelectedApplications->pluck('id'))
                        ->update([
                            'application_status' => 'Not Selected',
                            'selected_by_employee_id' => $employee->id,
                            'selected_at' => $winnerSelectedAt,
                        ]);

                    BrokerApplicationReviewDraft::query()
                        ->whereIn('broker_application_id', $notSelectedApplications->pluck('id'))
                        ->delete();

                    $archivedApplicantAccountCount = $this->archiveFinalizedApplicantAccounts(
                        $notSelectedApplications->pluck('user_id')->unique()->values()
                    );
                }

                $this->sendWinnerEmail($user->email, $broker, $selectedStall);
            });
        } catch (\Throwable $exception) {
            Log::error('Unable to confirm winner for broker application.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'Failed to confirm winner. Please try again.');
        }

        $message = 'Winner selected successfully and broker account has been activated.';

        if ($notSelectedApplicantCount > 0) {
            $message .= ' ' . $notSelectedApplicantCount . ' remaining application'
                . ($notSelectedApplicantCount === 1 ? ' was' : 's were')
                . ' marked Not Selected.';
        }

        if ($archivedApplicantAccountCount > 0) {
            $message .= ' ' . $archivedApplicantAccountCount . ' applicant-only account'
                . ($archivedApplicantAccountCount === 1 ? ' was' : 's were')
                . ' archived/deactivated.';
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('success', $message);
    }

    /**
     * Resolve the employee profile attached to the logged-in LEEO account.
     */
    private function resolveCurrentEmployee(): ?Employee
    {
        return Auth::user()?->employee;
    }

    /**
     * Get vacant stalls that can still be awarded to a winning applicant.
     */
    private function availableWinnerStalls(): Collection
    {
        return ApplicationOpening::query()
            ->whereIn('opening_status', ['Open', 'Closed'])
            ->whereHas('stall', function ($query) {
                $query->whereIn('stall_status', ApplicationOpening::AVAILABLE_STALL_STATUSES);
            })
            ->with('stall:id,stall_number,stall_status')
            ->get()
            ->pluck('stall')
            ->filter()
            ->unique('id')
            ->sortBy('stall_number', SORT_NATURAL)
            ->values();
    }

    /**
     * Deactivate applicant-only user accounts once all their applications are finalized.
     */
    private function archiveFinalizedApplicantAccounts(Collection $userIds): int
    {
        $terminalStatuses = ['Rejected', 'Winner', 'Not Selected'];

        return User::query()
            ->whereIn('id', $userIds->filter()->unique()->values())
            ->where('status', UserStatusConstant::ACTIVE)
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
            })
            ->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->whereIn('role_name', [
                    RoleStatusConstant::ADMIN,
                    RoleStatusConstant::STAFF,
                    RoleStatusConstant::BROKER,
                ]);
            })
            ->whereDoesntHave('brokerApplications', function ($applicationQuery) use ($terminalStatuses) {
                $applicationQuery->whereNotIn('application_status', $terminalStatuses);
            })
            ->update(['status' => UserStatusConstant::DEACTIVATED]);
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
