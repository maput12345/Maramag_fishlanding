<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ApplicationStatusConstant;
use App\Constants\OpeningStatusConstant;
use App\Constants\RoleStatusConstant;
use App\Constants\RequirementVerificationStatusConstant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBrokerApplicationRequest;
use App\Mail\BrokerApplicationNeedsRevision;
use App\Mail\BrokerApplicationQualifiedForBidding;
use App\Mail\BrokerApplicationRejected;
use App\Http\Requests\StoreApplicationOpeningRequest;
use App\Http\Requests\StoreRequirementTypeRequest;
use App\Http\Requests\StoreStallRequest;
use App\Http\Requests\UpdateApplicationOpeningRequest;
use App\Mail\BrokerWinnerSelected;
use App\Models\ApplicationOpening;
use App\Models\SubmittedRequirement;
use App\Models\Broker;
use App\Models\BrokerApplication;
use App\Models\ApplicationReviewDraft;
use App\Models\Employee;
use App\Models\OpeningBatch;
use App\Models\RequirementType;
use App\Models\Role;
use App\Models\Stall;
use App\Models\StallImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $openingBatchId = $request->integer('opening_batch_id') ?: null;
        $stallId = $request->integer('stall_id') ?: null;
        $applicationDate = $request->get('application_date');
        $submissionType = $request->get('submission_type');
        $applicationStatusTabs = [
            'ongoing_review' => [
                'label' => 'Ongoing Review',
                'statuses' => ApplicationStatusConstant::ongoingReviewStatuses(),
            ],
            'winners' => [
                'label' => 'Winners',
                'statuses' => ApplicationStatusConstant::winnerStatuses(),
            ],
            'not_selected' => [
                'label' => 'Not Selected',
                'statuses' => ApplicationStatusConstant::notSelectedStatuses(),
            ],
        ];
        $activeApplicationTab = $request->get('tab', 'ongoing_review');

        if (!array_key_exists($activeApplicationTab, $applicationStatusTabs)) {
            $activeApplicationTab = 'ongoing_review';
        }

        $filteredApplicationsQuery = BrokerApplication::query()
            ->when($openingBatchId, function ($query) use ($openingBatchId) {
                $query->where(function ($batchQuery) use ($openingBatchId) {
                    $batchQuery
                        ->where('opening_batch_id', $openingBatchId)
                        ->orWhereHas('applicationOpening', function ($openingQuery) use ($openingBatchId) {
                            $openingQuery->where('opening_batch_id', $openingBatchId);
                        });
                });
            })
            ->when($stallId, function ($query) use ($stallId) {
                $query->where(function ($stallQuery) use ($stallId) {
                    $stallQuery
                        ->whereHas('openingBatch.applicationOpenings', function ($openingQuery) use ($stallId) {
                            $openingQuery->where('stall_id', $stallId);
                        })
                        ->orWhereHas('applicationOpening', function ($openingQuery) use ($stallId) {
                            $openingQuery->where('stall_id', $stallId);
                        });
                });
            })
            ->when($applicationDate, function ($query) use ($applicationDate) {
                $query->whereDate('submitted_at', $applicationDate);
            })
            ->when($submissionType === 'new', function ($query) {
                $query->where('application_status', ApplicationStatusConstant::SUBMITTED)
                    ->where(function ($newQuery) {
                        $newQuery
                            ->whereNull('revision_resubmitted_at')
                            ->orWhere('revision_count', 0);
                    });
            })
            ->when($submissionType === 'resubmitted', function ($query) {
                $query->where('application_status', ApplicationStatusConstant::SUBMITTED)
                    ->whereNotNull('revision_resubmitted_at')
                    ->where('revision_count', '>', 0);
            });

        $submissionSummary = [
            'new' => (clone $filteredApplicationsQuery)
                ->where('application_status', ApplicationStatusConstant::SUBMITTED)
                ->where(function ($query) {
                    $query
                        ->whereNull('revision_resubmitted_at')
                        ->orWhere('revision_count', 0);
                })
                ->count(),
            'resubmitted' => (clone $filteredApplicationsQuery)
                ->where('application_status', ApplicationStatusConstant::SUBMITTED)
                ->whereNotNull('revision_resubmitted_at')
                ->where('revision_count', '>', 0)
                ->count(),
            'needs_review' => (clone $filteredApplicationsQuery)
                ->where('application_status', ApplicationStatusConstant::SUBMITTED)
                ->count(),
            'qualified' => (clone $filteredApplicationsQuery)
                ->where('application_status', ApplicationStatusConstant::QUALIFIED)
                ->count(),
        ];

        $applicationTabCounts = collect($applicationStatusTabs)
            ->mapWithKeys(function (array $tab, string $tabKey) use ($filteredApplicationsQuery, $status) {
                return [
                    $tabKey => (clone $filteredApplicationsQuery)
                        ->when($status, function ($query) use ($status) {
                            $query->where('application_status', $status);
                        })
                        ->whereIn('application_status', $tab['statuses'])
                        ->count(),
                ];
            })
            ->all();

        $applications = (clone $filteredApplicationsQuery)
            ->select([
                'id',
                'user_id',
                'application_opening_id',
                'opening_batch_id',
                'selected_stall_id',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'application_status',
                'submitted_at',
                'revision_resubmitted_at',
                'revision_count',
                'review_date',
            ])
            ->with([
                'user:id,email',
                'openingBatch:id,start_date,end_date',
                'openingBatch.applicationOpenings:id,opening_batch_id,stall_id',
                'openingBatch.applicationOpenings.stall:id,stall_number',
                'applicationOpening:id,stall_id,opening_batch_id',
                'applicationOpening.stall:id,stall_number',
                'applicationOpening.openingBatch:id,start_date,end_date',
                'applicationOpening.openingBatch.applicationOpenings:id,opening_batch_id,stall_id',
                'applicationOpening.openingBatch.applicationOpenings.stall:id,stall_number',
                'selectedStall:id,stall_number',
            ])
            ->when($status, function ($query) use ($status) {
                $query->where('application_status', $status);
            })
            ->whereIn('application_status', $applicationStatusTabs[$activeApplicationTab]['statuses'])
            ->latest('submitted_at')
            ->paginate(10);

        $openingBatches = OpeningBatch::query()
            ->with('applicationOpenings.stall:id,stall_number')
            ->latest()
            ->get();
        $stalls = Stall::query()
            ->select(['id', 'stall_number'])
            ->get()
            ->sortBy('stall_number', SORT_NATURAL)
            ->values();

        return view('admin.applications.index', compact(
            'applications',
            'status',
            'submissionType',
            'submissionSummary',
            'openingBatchId',
            'stallId',
            'applicationDate',
            'openingBatches',
            'stalls',
            'applicationStatusTabs',
            'activeApplicationTab',
            'applicationTabCounts'
        ));
    }

    /**
     * Move selected submitted applications into active LEEO review.
     */
    public function bulkMarkUnderReview(Request $request): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');

        $validated = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer', 'exists:BrokerApplication,id'],
        ], [
            'application_ids.required' => 'Select at least one submitted application.',
        ]);

        $updatedCount = BrokerApplication::query()
            ->whereIn('id', $validated['application_ids'])
            ->where('application_status', ApplicationStatusConstant::SUBMITTED)
            ->update([
                'application_status' => ApplicationStatusConstant::UNDER_REVIEW,
                'reviewed_by_employee_id' => $employee->id,
                'review_date' => now(),
                'updated_at' => now(),
            ]);

        if ($updatedCount === 0) {
            return back()->with('info', 'No submitted applications were available to mark as under review.');
        }

        return back()->with('success', $updatedCount . ' application' . ($updatedCount === 1 ? '' : 's') . ' marked as under review.');
    }

    /**
     * Request an applicant-specific extra document during review.
     */
    public function storeAdditionalRequirement(Request $request, BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');

        $validated = $request->validate([
            'custom_title' => ['required', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string', 'max:1000'],
        ], [
            'custom_title.required' => 'Enter the additional requirement name.',
        ]);

        DB::transaction(function () use ($application, $employee, $validated) {
            SubmittedRequirement::create([
                'application_id' => $application->id,
                'requirement_type_id' => null,
                'custom_title' => $validated['custom_title'],
                'custom_description' => $validated['custom_description'] ?? null,
                'is_additional' => true,
                'file_path' => '',
                'verification_status' => RequirementVerificationStatusConstant::NEEDS_REVISION,
                'verified_by_employee_id' => $employee->id,
                'verification_date' => now(),
                'remarks' => $validated['custom_description'] ?? 'Please upload this additional requirement.',
                'uploaded_at' => null,
            ]);

            $application->update([
                'application_status' => ApplicationStatusConstant::NEEDS_REVISION,
                'reviewed_by_employee_id' => $employee->id,
                'review_date' => now(),
                'remarks' => $validated['custom_description'] ?? 'Please upload the additional requested requirement.',
            ]);
        });

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Additional requirement requested from the applicant.');
    }

    /**
     * Correct an applicant-specific extra document request.
     */
    public function updateAdditionalRequirement(Request $request, BrokerApplication $application, SubmittedRequirement $requirement): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');
        $this->abortUnlessApplicantSpecificRequirement($application, $requirement);

        $validated = $request->validate([
            'custom_title' => ['required', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string', 'max:1000'],
        ], [
            'custom_title.required' => 'Enter the additional requirement name.',
        ]);

        $requirement->update([
            'custom_title' => $validated['custom_title'],
            'custom_description' => $validated['custom_description'] ?? null,
            'remarks' => $validated['custom_description'] ?? 'Please upload this additional requirement.',
            'verified_by_employee_id' => $employee->id,
            'verification_date' => now(),
        ]);

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Additional requirement updated.');
    }

    /**
     * Remove an applicant-specific extra document request before a file is uploaded.
     */
    public function destroyAdditionalRequirement(BrokerApplication $application, SubmittedRequirement $requirement): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');
        $this->abortUnlessApplicantSpecificRequirement($application, $requirement);

        if ($requirement->file_path) {
            return redirect()->route('admin.applications.show', $application)
                ->with('error', 'This additional requirement already has an uploaded file. Edit the title or instruction instead of deleting the record.');
        }

        $requirement->delete();

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Additional requirement deleted.');
    }

    /**
     * Show the stall management workspace.
     */
    public function stallsIndex(): View
    {
        $workspaceData = $this->stallWorkspaceData();

        return view('admin.stalls.index', $workspaceData);
    }

    /**
     * Show the requirement checklist manager.
     */
    public function stallsRequirements(): View
    {
        $workspaceData = $this->stallWorkspaceData();

        return view('admin.stalls.requirements', $workspaceData);
    }

    /**
     * Show stall occupancy and bidding overview.
     */
    public function stallsOverview(Request $request): View
    {
        $workspaceData = $this->stallWorkspaceData(
            $request->string('stall_search')->trim()->toString(),
            true
        );

        return view('admin.stalls.overview', $workspaceData);
    }

    /**
     * Show one application in detail for review.
     */
    public function show(BrokerApplication $application): View
    {
        $application->load([
            'user:id,email',
            'openingBatch:id,start_date,end_date,bidding_date,bidding_time,bidding_location',
            'openingBatch.applicationOpenings:id,opening_batch_id,stall_id',
            'openingBatch.applicationOpenings.stall:id,stall_number',
            'applicationOpening:id,stall_id,opening_batch_id',
            'applicationOpening.openingBatch:id,start_date,end_date,bidding_date,bidding_time,bidding_location',
            'applicationOpening.stall:id,stall_number',
            'requirements:id,application_id,requirement_type_id,custom_title,custom_description,is_additional,file_path,document_number,issuing_office,verification_status,remarks,uploaded_at',
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
            ? ApplicationReviewDraft::query()
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
        $createdStallNumber = null;

        DB::transaction(function () use ($validated, $storedImagePaths, &$createdStallNumber) {
            $nextStallNumber = Stall::nextStallNumberFrom(
                Stall::query()
                    ->lockForUpdate()
                    ->pluck('stall_number')
            );
            $createdStallNumber = $nextStallNumber;

            $stall = Stall::create([
                'stall_number' => $nextStallNumber,
                'stall_status' => OpeningStatusConstant::STALL_VACANT,
                'length_meters' => $validated['length_meters'],
                'width_meters' => $validated['width_meters'],
                'area_sqm' => round((float) $validated['length_meters'] * (float) $validated['width_meters'], 2),
                'address' => $validated['address'],
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
            ->with('success', 'Stall ' . $createdStallNumber . ' created successfully.');
    }

    /**
     * Add photos to an existing stall gallery.
     */
    public function storeStallPhotos(Request $request, Stall $stall): RedirectResponse
    {
        $validated = $request->validate([
            'stall_images' => ['required', 'array', 'min:1', 'max:6'],
            'stall_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $currentPhotoCount = $stall->stallImages()->count();
        $incomingPhotoCount = count($validated['stall_images'] ?? []);

        if ($currentPhotoCount + $incomingPhotoCount > 6) {
            return redirect()->back()
                ->withErrors(['stall_images' => 'A stall can have up to 6 photos. Delete an old photo before adding more.'])
                ->withInput();
        }

        $storedImagePaths = collect($request->file('stall_images', []))
            ->filter()
            ->map(fn ($uploadedImage) => $uploadedImage->store('stalls', 'public'))
            ->values();

        if ($storedImagePaths->isEmpty()) {
            return redirect()->back()
                ->withErrors(['stall_images' => 'Please choose at least one stall photo.'])
                ->withInput();
        }

        DB::transaction(function () use ($stall, $storedImagePaths, $currentPhotoCount) {
            $stall->stallImages()->createMany(
                $storedImagePaths->map(function (string $imagePath, int $index) use ($currentPhotoCount): array {
                    return [
                        'image_path' => $imagePath,
                        'sort_order' => $currentPhotoCount + $index,
                    ];
                })->all()
            );

            if (!$stall->stall_image_path) {
                $stall->update(['stall_image_path' => $storedImagePaths->first()]);
            }
        });

        return redirect()->back()
            ->with('success', $stall->display_name . ' photos updated successfully.');
    }

    /**
     * Remove a wrong photo from a stall gallery.
     */
    public function destroyStallPhoto(Stall $stall, StallImage $stallImage): RedirectResponse
    {
        abort_unless((int) $stallImage->stall_id === (int) $stall->id, 404);

        $deletedImagePath = $stallImage->image_path;

        DB::transaction(function () use ($stall, $stallImage, $deletedImagePath) {
            $stallImage->delete();

            if ($stall->stall_image_path === $deletedImagePath) {
                $nextPrimaryImagePath = $stall->stallImages()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->value('image_path');

                $stall->update(['stall_image_path' => $nextPrimaryImagePath]);
            }
        });

        Storage::disk('public')->delete($deletedImagePath);

        return redirect()->back()
            ->with('success', $stall->display_name . ' photo removed successfully.');
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

        return redirect()->route('admin.stalls.requirements.index')
            ->with('success', 'Requirement added to the master list.');
    }

    /**
     * Correct a reusable requirement in the LEEO requirement master list.
     */
    public function updateRequirementType(Request $request, RequirementType $requirementType): RedirectResponse
    {
        $validated = $request->validate([
            'requirement_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('RequirementType', 'requirement_name')->ignore($requirementType->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'audience' => [
                'required',
                Rule::in([
                    RequirementType::APPLICANT_TYPE_NATURAL,
                    RequirementType::APPLICANT_TYPE_JURIDICAL,
                    RequirementType::APPLICANT_TYPE_BOTH,
                ]),
            ],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $requirementType->update([
            'requirement_name' => $validated['requirement_name'],
            'description' => $validated['description'] ?? null,
            'audience' => $validated['audience'],
            'is_required' => $request->boolean('is_required'),
        ]);

        return redirect()->route('admin.stalls.requirements.index')
            ->with('success', 'Requirement updated successfully.');
    }

    /**
     * Remove an unused reusable requirement from the LEEO requirement master list.
     */
    public function destroyRequirementType(RequirementType $requirementType): RedirectResponse
    {
        $requirementType->loadCount(['openingRequirements', 'applicationRequirements']);

        if ($requirementType->opening_requirements_count > 0 || $requirementType->application_requirements_count > 0) {
            return redirect()->route('admin.stalls.requirements.index')
                ->with('error', 'This requirement is already used by a vacancy or application. Edit it instead of deleting it.');
        }

        $requirementType->delete();

        return redirect()->route('admin.stalls.requirements.index')
            ->with('success', 'Requirement deleted successfully.');
    }

    /**
     * Open a new application window for a vacant stall.
     */
    public function storeOpening(StoreApplicationOpeningRequest $request): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();

        abort_if(!$employee, 403, 'Only LEEO employee accounts can open applications.');

        $validated = $request->validated();
        $selectedStallIds = collect($validated['stall_ids'])
            ->map(fn ($stallId) => (int) $stallId)
            ->unique()
            ->values();
        $selectedRequirementIds = collect($validated['requirement_type_ids'])
            ->map(fn ($requirementId) => (int) $requirementId)
            ->unique()
            ->values();

        try {
            DB::transaction(function () use ($validated, $employee, $selectedStallIds, $selectedRequirementIds) {
                $requirementTypes = RequirementType::query()
                    ->whereIn('id', $selectedRequirementIds)
                    ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END')
                    ->orderBy('sort_order')
                    ->orderBy('requirement_name')
                    ->get();

                $requirementSyncPayload = $requirementTypes->mapWithKeys(function (RequirementType $requirementType, int $index) {
                    return [
                        $requirementType->id => [
                            'is_required' => $requirementType->is_required,
                            'audience' => $requirementType->audience ?: RequirementType::APPLICANT_TYPE_BOTH,
                            'sort_order' => $requirementType->sort_order ?: (($index + 1) * 10),
                        ],
                    ];
                })->all();

                $stalls = Stall::query()
                    ->whereIn('id', $selectedStallIds)
                    ->where('stall_status', OpeningStatusConstant::STALL_VACANT)
                    ->lockForUpdate()
                    ->get();

                if ($stalls->count() !== $selectedStallIds->count()) {
                    throw new \RuntimeException('One or more selected stalls are no longer vacant.');
                }

                $openingBatch = OpeningBatch::create([
                    'opened_by_employee_id' => $employee->id,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'bidding_date' => $validated['bidding_date'],
                    'bidding_time' => $validated['bidding_time'],
                    'bidding_location' => $validated['bidding_location'],
                ]);

                foreach ($stalls as $stall) {
                    $opening = ApplicationOpening::create([
                        'stall_id' => $stall->id,
                        'opening_batch_id' => $openingBatch->id,
                        'opened_by_employee_id' => $employee->id,
                        'opening_status' => OpeningStatusConstant::OPEN,
                    ]);

                    $opening->requirementTypes()->sync($requirementSyncPayload);
                    $stall->update(['stall_status' => OpeningStatusConstant::STALL_OPEN_FOR_APPLICATION]);
                }
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.stalls.index')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.stalls.index')
            ->with('success', 'Application opening created for ' . $selectedStallIds->count() . ' stall' . ($selectedStallIds->count() === 1 ? '' : 's') . '.');
    }

    /**
     * Update the opening status without removing the record.
     */
    public function updateOpeningStatus(Request $request, ApplicationOpening $opening): RedirectResponse
    {
        $request->validate([
            'opening_status' => ['required', Rule::in([
                OpeningStatusConstant::STALL_VACANT,
                OpeningStatusConstant::STALL_OCCUPIED,
                OpeningStatusConstant::CANCELLED,
            ])],
        ]);

        $selectedStatus = $request->input('opening_status');

        if ($selectedStatus === OpeningStatusConstant::STALL_OCCUPIED) {
            $opening->update(['opening_status' => OpeningStatusConstant::COMPLETED]);
            $opening->stall()->update(['stall_status' => OpeningStatusConstant::STALL_OCCUPIED]);
        } elseif ($selectedStatus === OpeningStatusConstant::CANCELLED) {
            $opening->update(['opening_status' => OpeningStatusConstant::CANCELLED]);
            $opening->stall()->update(['stall_status' => OpeningStatusConstant::STALL_VACANT]);
        } else {
            $opening->update(['opening_status' => OpeningStatusConstant::OPEN]);
            $opening->stall()->update(['stall_status' => OpeningStatusConstant::STALL_OPEN_FOR_APPLICATION]);
        }

        return redirect()->route('admin.stalls.overview')
            ->with('success', 'Application opening updated successfully.');
    }

    /**
     * Update the bidding schedule details for an opening.
     */
    public function updateOpening(UpdateApplicationOpeningRequest $request, ApplicationOpening $opening): RedirectResponse
    {
        $opening->loadMissing('openingBatch');
        $originalBiddingDate = optional($opening->bidding_date)->toDateString();
        $originalBiddingTime = optional($opening->bidding_time)->format('H:i');
        $originalBiddingLocation = $opening->bidding_location;

        $opening->openingBatch?->update([
            'bidding_date' => $request->input('bidding_date'),
            'bidding_time' => $request->input('bidding_time'),
            'bidding_location' => $request->input('bidding_location'),
        ]);

        $opening = $opening->fresh(['openingBatch']);
        $updatedBiddingDate = optional($opening->bidding_date)->toDateString();
        $updatedBiddingTime = optional($opening->bidding_time)->format('H:i');
        $updatedBiddingLocation = $opening->bidding_location;
        $qualifiedApplicantsNotified = 0;

        if ($originalBiddingDate !== $updatedBiddingDate || $originalBiddingTime !== $updatedBiddingTime || $originalBiddingLocation !== $updatedBiddingLocation) {
            $qualifiedApplications = $opening->brokerApplications()
                ->where('application_status', ApplicationStatusConstant::QUALIFIED)
                ->with([
                    'user:id,email',
                    'applicationOpening:id,stall_id,opening_batch_id',
                    'applicationOpening.openingBatch:id,start_date,bidding_date,bidding_time,bidding_location',
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

        return redirect()->route('admin.stalls.overview')
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
            'application_status' => ['nullable', Rule::in(ApplicationStatusConstant::reviewStatuses())],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['nullable', 'array'],
            'requirements.*.id' => ['required_with:requirements', 'integer'],
            'requirements.*.verification_status' => ['nullable', Rule::in(RequirementVerificationStatusConstant::all())],
            'requirements.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowedRequirementIds = $application->requirements()
            ->pluck('id')
            ->map(fn ($requirementId) => (int) $requirementId);

        $requirements = collect($validated['requirements'] ?? [])
            ->map(function (array $requirementPayload) {
                return [
                    'id' => (int) $requirementPayload['id'],
                    'verification_status' => $requirementPayload['verification_status'] ?? RequirementVerificationStatusConstant::PENDING,
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

        $draft = ApplicationReviewDraft::updateOrCreate(
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
        $finalApplicationStatus = $this->resolveFinalApplicationStatus(
            $request->input('application_status'),
            $request->input('requirements', [])
        );
        $previousApplicationStatus = $application->application_status;
        $shouldSendQualifiedEmail = $previousApplicationStatus !== ApplicationStatusConstant::QUALIFIED
            && $finalApplicationStatus === ApplicationStatusConstant::QUALIFIED;
        $shouldSendNeedsRevisionEmail = $previousApplicationStatus !== ApplicationStatusConstant::NEEDS_REVISION
            && $finalApplicationStatus === ApplicationStatusConstant::NEEDS_REVISION;
        $shouldSendRejectedEmail = $previousApplicationStatus !== ApplicationStatusConstant::REJECTED
            && $finalApplicationStatus === ApplicationStatusConstant::REJECTED;

        abort_if(!$employee, 403, 'Only LEEO employee accounts can review applications.');

        DB::transaction(function () use ($request, $application, $employee, $finalApplicationStatus) {
            $requirements = $application->requirements()
                ->select(['id', 'application_id'])
                ->get()
                ->keyBy('id');
            $applicationRemarks = $request->input('remarks');

            foreach ($request->input('requirements', []) as $requirementPayload) {
                /** @var SubmittedRequirement $requirement */
                $requirement = $requirements->get((int) $requirementPayload['id']);

                if (!$requirement) {
                    continue;
                }

                $status = $requirementPayload['verification_status'];
                if ($finalApplicationStatus === ApplicationStatusConstant::REJECTED && $status === RequirementVerificationStatusConstant::PENDING) {
                    $status = RequirementVerificationStatusConstant::REJECTED;
                }

                $requirement->update([
                    'verification_status' => $status,
                    'verified_by_employee_id' => $status === RequirementVerificationStatusConstant::PENDING ? null : $employee->id,
                    'verification_date' => $status === RequirementVerificationStatusConstant::PENDING ? null : now(),
                    'remarks' => $status === RequirementVerificationStatusConstant::VERIFIED
                        ? null
                        : (($requirementPayload['remarks'] ?? null) ?: ($status === RequirementVerificationStatusConstant::REJECTED ? $applicationRemarks : null)),
                ]);
            }

            $application->update([
                'application_status' => $finalApplicationStatus,
                'reviewed_by_employee_id' => $employee->id,
                'review_date' => now(),
                'remarks' => $applicationRemarks,
            ]);

            ApplicationReviewDraft::query()
                ->where('broker_application_id', $application->id)
                ->delete();
        });

        if ($shouldSendQualifiedEmail) {
            $application->refresh()->load([
                'user:id,email',
                'applicationOpening:id,stall_id,opening_batch_id',
                'applicationOpening.openingBatch:id,start_date,bidding_date,bidding_time,bidding_location',
                'applicationOpening.stall:id,stall_number',
            ]);

            $this->sendQualifiedForBiddingEmail($application);
        }

        if ($shouldSendNeedsRevisionEmail || $shouldSendRejectedEmail) {
            $application->refresh()->load(['user:id,email']);

            if ($shouldSendNeedsRevisionEmail) {
                $this->sendNeedsRevisionEmail($application);
            }

            if ($shouldSendRejectedEmail) {
                $this->sendRejectedEmail($application);
            }
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Application review saved successfully.');
    }

    /**
     * Keep the overall application status aligned with requirement decisions.
     */
    private function resolveFinalApplicationStatus(string $requestedStatus, array $requirementPayloads): string
    {
        if ($requestedStatus === ApplicationStatusConstant::REJECTED) {
            return ApplicationStatusConstant::REJECTED;
        }

        $statuses = collect($requirementPayloads)
            ->filter(fn ($payload) => is_array($payload))
            ->pluck('verification_status');

        if (
            $statuses->contains(RequirementVerificationStatusConstant::REJECTED)
            || $statuses->contains(RequirementVerificationStatusConstant::NEEDS_REVISION)
        ) {
            return ApplicationStatusConstant::NEEDS_REVISION;
        }

        if ($statuses->isNotEmpty() && $statuses->every(fn ($status) => $status === RequirementVerificationStatusConstant::VERIFIED)) {
            return ApplicationStatusConstant::QUALIFIED;
        }

        return $requestedStatus === ApplicationStatusConstant::QUALIFIED ? ApplicationStatusConstant::UNDER_REVIEW : $requestedStatus;
    }

    /**
     * Record the offline winner and convert them into the new broker.
     */
    public function selectWinner(Request $request, BrokerApplication $application): RedirectResponse
    {
        $employee = $this->resolveCurrentEmployee();
        $notSelectedApplicantCount = 0;

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

        if ($application->application_status !== ApplicationStatusConstant::QUALIFIED) {
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
            DB::transaction(function () use ($application, $employee, $validated, &$notSelectedApplicantCount) {
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

                if (Broker::query()->where('stall_id', $selectedStall->id)->exists()) {
                    throw new \RuntimeException($selectedStall->display_name . ' is still assigned to an existing broker account.');
                }

                $winnerSelectedAt = now();

                $application->update([
                    'application_status' => ApplicationStatusConstant::WINNER,
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
                    ->whereIn('opening_status', [OpeningStatusConstant::OPEN, OpeningStatusConstant::CLOSED])
                    ->latest('id')
                    ->first()
                    ?->update(['opening_status' => OpeningStatusConstant::COMPLETED]);

                $selectedStall->update(['stall_status' => OpeningStatusConstant::STALL_OCCUPIED]);

                if ($this->availableWinnerStalls()->isEmpty()) {
                    $notSelectedApplications = BrokerApplication::query()
                        ->where('id', '!=', $application->id)
                        ->whereNotIn('application_status', [
                            ApplicationStatusConstant::REJECTED,
                            ApplicationStatusConstant::WINNER,
                            ApplicationStatusConstant::NOT_SELECTED,
                        ])
                        ->get(['id', 'user_id']);

                    $notSelectedApplicantCount = $notSelectedApplications->count();

                    BrokerApplication::query()
                        ->whereKey($notSelectedApplications->pluck('id'))
                        ->update([
                            'application_status' => ApplicationStatusConstant::NOT_SELECTED,
                            'selected_by_employee_id' => $employee->id,
                            'selected_at' => $winnerSelectedAt,
                        ]);

                    ApplicationReviewDraft::query()
                        ->whereIn('broker_application_id', $notSelectedApplications->pluck('id'))
                        ->delete();
                }

                $this->sendWinnerEmail($user->email, $broker, $selectedStall);
            });
        } catch (\Throwable $exception) {
            Log::error('Unable to confirm winner for broker application.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.applications.show', $application)
                ->with('error', $exception instanceof \RuntimeException
                    ? $exception->getMessage()
                    : 'Failed to confirm winner. Please try again.');
        }

        $message = 'Winner selected successfully and broker account has been activated.';

        if ($notSelectedApplicantCount > 0) {
            $message .= ' ' . $notSelectedApplicantCount . ' remaining application'
                . ($notSelectedApplicantCount === 1 ? ' was' : 's were')
                . ' marked Not Selected.';
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

    private function abortUnlessApplicantSpecificRequirement(BrokerApplication $application, SubmittedRequirement $requirement): void
    {
        abort_unless(
            (int) $requirement->application_id === (int) $application->id && $requirement->is_additional,
            404
        );
    }

    /**
     * Get vacant stalls that can still be awarded to a winning applicant.
     */
    private function availableWinnerStalls(): Collection
    {
        return ApplicationOpening::query()
            ->whereIn('opening_status', [OpeningStatusConstant::OPEN, OpeningStatusConstant::CLOSED])
            ->whereHas('stall', function ($query) {
                $query
                    ->whereIn('stall_status', ApplicationOpening::AVAILABLE_STALL_STATUSES)
                    ->whereDoesntHave('broker');
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
     * Shared data used by the separated stall management pages.
     */
    private function stallWorkspaceData(?string $stallSearch = null, bool $paginateStalls = false): array
    {
        $stalls = Stall::query()
            ->select(['id', 'stall_number', 'stall_status', 'length_meters', 'width_meters', 'area_sqm', 'address', 'remarks', 'stall_image_path'])
            ->when($stallSearch, function ($query) use ($stallSearch) {
                $normalizedSearch = trim(preg_replace('/^stall\s+/i', '', $stallSearch));

                $query->where(function ($searchQuery) use ($stallSearch, $normalizedSearch) {
                    $searchQuery
                        ->where('stall_number', 'like', '%' . $stallSearch . '%')
                        ->orWhere('stall_status', 'like', '%' . $stallSearch . '%')
                        ->orWhere('address', 'like', '%' . $stallSearch . '%')
                        ->orWhere('remarks', 'like', '%' . $stallSearch . '%');

                    if ($normalizedSearch !== $stallSearch && $normalizedSearch !== '') {
                        $searchQuery->orWhere('stall_number', 'like', '%' . $normalizedSearch . '%');
                    }
                });
            })
            ->with([
                'stallImages',
                'applicationOpenings' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'stall_id',
                            'opening_batch_id',
                            'opening_status',
                            'created_at',
                        ])
                        ->with('openingBatch:id,start_date,end_date,bidding_date,bidding_time,bidding_location')
                        ->withCount(['brokerApplications', 'requirementTypes'])
                        ->latest();
                },
            ])
            ->get()
            ->sortBy('stall_number', SORT_NATURAL)
            ->values();

        if ($paginateStalls) {
            $stalls = $this->paginateCollection($stalls, 10);
        }

        $vacantStalls = $stalls
            ->filter(fn (Stall $stall) => $stall->stall_status === OpeningStatusConstant::STALL_VACANT)
            ->values();

        $openings = ApplicationOpening::query()
            ->select([
                'id',
                'stall_id',
                'opening_batch_id',
                'opening_status',
                'created_at',
            ])
            ->with([
                'openingBatch:id,start_date,end_date,bidding_date,bidding_time,bidding_location',
                'stall:id,stall_number,stall_status',
            ])
            ->withCount('brokerApplications')
            ->latest()
            ->get();

        return [
            'stalls' => $stalls,
            'vacantStalls' => $vacantStalls,
            'openings' => $openings,
            'requirementTypes' => RequirementType::selectableChecklistTypes(),
            'nextStallNumber' => Stall::nextStallNumber(),
            'stallSearch' => $stallSearch,
        ];
    }

    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
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

    /**
     * Send the needs-revision notification email without breaking review flow.
     */
    private function sendNeedsRevisionEmail(BrokerApplication $application): void
    {
        $email = $application->user?->email;

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new BrokerApplicationNeedsRevision($application));
        } catch (\Throwable $exception) {
            Log::warning('Unable to send broker needs-revision email.', [
                'application_id' => $application->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send the rejected notification email without breaking review flow.
     */
    private function sendRejectedEmail(BrokerApplication $application): void
    {
        $email = $application->user?->email;

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new BrokerApplicationRejected($application));
        } catch (\Throwable $exception) {
            Log::warning('Unable to send broker rejected email.', [
                'application_id' => $application->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
