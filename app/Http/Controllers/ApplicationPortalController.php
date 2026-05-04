<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrokerApplicationRequest;
use App\Http\Requests\UpdateBrokerApplicationRevisionRequest;
use App\Models\ApplicationOpening;
use App\Models\SubmittedRequirement;
use App\Models\BrokerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationPortalController extends Controller
{
    /**
     * Show the applicant landing page with open stall applications.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        $openings = ApplicationOpening::with(['stall.stallImages'])
            ->availableForApplication()
            ->withCount('brokerApplications')
            ->orderBy('start_date')
            ->get();

        $applications = $user->brokerApplications()
            ->with([
                'applicationOpening.stall',
                'selectedStall',
                'requirements.requirementType',
                'reviewedBy',
                'selectedBy',
                'broker',
            ])
            ->latest()
            ->get();

        $primaryOpening = $openings->first();
        $currentApplication = $applications
            ->whereNotIn('application_status', ['Rejected', 'Not Selected'])
            ->first();

        return view('applications.index', compact('openings', 'applications', 'primaryOpening', 'currentApplication'));
    }

    /**
     * Show the broker application form for a specific opening.
     */
    public function create(ApplicationOpening $opening): View|RedirectResponse
    {
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        if ($opening->opening_status !== 'Open' || !$opening->start_date || !$opening->end_date || !$opening->hasAvailableStall()) {
            return redirect()->route('applications.index')
                ->with('error', 'This stall opening is not currently accepting applications.');
        }

        if (now()->toDateString() < $opening->start_date->toDateString() || now()->toDateString() > $opening->end_date->toDateString()) {
            return redirect()->route('applications.index')
                ->with('error', 'This stall opening is already closed.');
        }

        $alreadyApplied = Auth::user()
            ->brokerApplications()
            ->whereNotIn('application_status', ['Rejected', 'Not Selected'])
            ->exists();

        if ($alreadyApplied) {
            return redirect()->route('applications.index')
                ->with('info', 'You already have an active application for the current open stalls.');
        }

        $requirementDefinitions = $opening->requirementDefinitionMap();
        $requirementTypes = $opening->resolvedRequirementTypes();

        $opening->loadMissing('stall.stallImages');

        return view('applications.create', compact('opening', 'requirementTypes', 'requirementDefinitions'));
    }

    /**
     * Persist a new broker application and its uploaded requirements.
     */
    public function store(StoreBrokerApplicationRequest $request, ApplicationOpening $opening): RedirectResponse
    {
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        $validated = $request->validated();
        $requirementTypes = $opening->resolvedRequirementTypes();

        DB::transaction(function () use ($validated, $opening, $requirementTypes, $request) {
            $application = BrokerApplication::create([
                'user_id' => Auth::id(),
                'application_opening_id' => $opening->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'address' => $validated['address'],
                'contact_number' => $validated['contact_number'],
                'application_status' => 'Submitted',
                'submitted_at' => now(),
            ]);

            foreach ($requirementTypes as $requirementType) {
                $requirementPayload = ($validated['requirements'] ?? [])[$requirementType->id] ?? [];
                $hasFile = $request->hasFile('requirements.' . $requirementType->id . '.file');
                $hasMetadata = collect([
                    $requirementPayload['document_number'] ?? null,
                    $requirementPayload['issuing_office'] ?? null,
                    $requirementPayload['issue_date'] ?? null,
                    $requirementPayload['expiry_date'] ?? null,
                ])->filter()->isNotEmpty();

                if (!$hasFile && !$hasMetadata) {
                    continue;
                }

                $filePath = $hasFile
                    ? $request->file('requirements.' . $requirementType->id . '.file')
                        ->store('broker-applications/' . $application->id, 'public')
                    : null;

                SubmittedRequirement::create([
                    'application_id' => $application->id,
                    'requirement_type_id' => $requirementType->id,
                    'file_path' => $filePath ?? '',
                    'document_number' => $requirementPayload['document_number'] ?? null,
                    'issuing_office' => $requirementPayload['issuing_office'] ?? null,
                    'issue_date' => $requirementPayload['issue_date'] ?? null,
                    'expiry_date' => $requirementPayload['expiry_date'] ?? null,
                    'verification_status' => 'Pending',
                    'uploaded_at' => now(),
                ]);
            }
        });

        return redirect()->route('applications.index')
            ->with('success', 'Your broker application has been submitted for LEEO review.');
    }

    /**
     * Show a single submitted application.
     */
    public function show(BrokerApplication $application): View|RedirectResponse
    {
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        abort_unless($application->user_id === Auth::id(), 403);

        $application->load([
            'applicationOpening.stall',
            'selectedStall',
            'requirements.requirementType',
            'reviewedBy',
            'selectedBy',
            'broker.stall',
        ]);

        return view('applications.show', compact('application'));
    }

    /**
     * Show the revision form when LEEO requests corrections.
     */
    public function edit(BrokerApplication $application): View|RedirectResponse
    {
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        abort_unless($application->user_id === Auth::id(), 403);

        if ($application->application_status !== 'Needs Revision') {
            return redirect()->route('applications.show', $application)
                ->with('info', 'This application is not currently open for revision.');
        }

        $application->load([
            'applicationOpening.stall',
            'requirements.requirementType',
        ]);

        return view('applications.edit', compact('application'));
    }

    /**
     * Resubmit corrections for an application marked Needs Revision.
     */
    public function update(UpdateBrokerApplicationRevisionRequest $request, BrokerApplication $application): RedirectResponse
    {
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        abort_unless($application->user_id === Auth::id(), 403);

        $validated = $request->validated();

        DB::transaction(function () use ($application, $validated, $request) {
            $application->update([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'address' => $validated['address'],
                'contact_number' => $validated['contact_number'],
                'application_status' => 'Submitted',
                'reviewed_by_employee_id' => null,
                'review_date' => null,
                'submitted_at' => now(),
            ]);

            $requirements = $application->requirements()
                ->get()
                ->keyBy('id');

            foreach ($validated['requirements'] as $requirementPayload) {
                /** @var SubmittedRequirement|null $requirement */
                $requirement = $requirements->get((int) $requirementPayload['id']);

                if (!$requirement) {
                    continue;
                }

                $fileInput = 'requirements.' . $requirement->id . '.file';
                $replacementFile = $requirementPayload['file'] ?? data_get($request->allFiles(), $fileInput);
                $hasReplacementFile = $replacementFile && $replacementFile->isValid();
                $updates = [
                    'document_number' => $requirementPayload['document_number'] ?? null,
                    'issuing_office' => $requirementPayload['issuing_office'] ?? null,
                    'issue_date' => $requirementPayload['issue_date'] ?? null,
                    'expiry_date' => $requirementPayload['expiry_date'] ?? null,
                ];

                if ($hasReplacementFile) {
                    $updates['file_path'] = $replacementFile->store('broker-applications/' . $application->id, 'public');
                    $updates['uploaded_at'] = now();
                }

                if ($hasReplacementFile || $requirement->verification_status !== 'Verified') {
                    $updates['verification_status'] = 'Pending';
                    $updates['verified_by_employee_id'] = null;
                    $updates['verification_date'] = null;
                    $updates['remarks'] = null;
                }

                $requirement->update($updates);
            }
        });

        return redirect()->route('applications.show', $application)
            ->with('success', 'Your revised application has been resubmitted for LEEO review.');
    }

    /**
     * Keep the portal limited to applicants waiting for a broker decision.
     */
    private function redirectNonApplicants(): ?RedirectResponse
    {
        $user = Auth::user();

        abort_if(!$user, 403);

        if ($user->isAdmin() || $user->isStaff()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('info', 'Applicant pages are only available to applicant accounts.');
        }

        if ($user->isBroker()) {
            return redirect()
                ->route('broker.dashboard')
                ->with('info', 'Your application account has been converted to a broker account.');
        }

        return null;
    }
}
