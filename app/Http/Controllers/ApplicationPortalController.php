<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrokerApplicationRequest;
use App\Models\ApplicationOpening;
use App\Models\ApplicationRequirement;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationPortalController extends Controller
{
    /**
     * Show the applicant landing page with open stall applications.
     */
    public function index(): View
    {
        $user = Auth::user();
        $this->ensureApplicantAccess();

        $openings = ApplicationOpening::with('stall')
            ->open()
            ->withCount('brokerApplications')
            ->orderBy('start_date')
            ->get();

        $applications = $user->brokerApplications()
            ->with([
                'applicationOpening.stall',
                'requirements.requirementType',
                'reviewedBy',
                'selectedBy',
                'broker',
            ])
            ->latest()
            ->get();

        return view('applications.index', compact('openings', 'applications'));
    }

    /**
     * Show the broker application form for a specific opening.
     */
    public function create(ApplicationOpening $opening): View|RedirectResponse
    {
        $this->ensureApplicantAccess();

        if ($opening->opening_status !== 'Open' || !$opening->start_date || !$opening->end_date) {
            return redirect()->route('applications.index')
                ->with('error', 'This stall opening is not currently accepting applications.');
        }

        if (now()->toDateString() < $opening->start_date->toDateString() || now()->toDateString() > $opening->end_date->toDateString()) {
            return redirect()->route('applications.index')
                ->with('error', 'This stall opening is already closed.');
        }

        $alreadyApplied = Auth::user()
            ->brokerApplications()
            ->where('application_opening_id', $opening->id)
            ->exists();

        if ($alreadyApplied) {
            return redirect()->route('applications.index')
                ->with('info', 'You have already submitted an application for this stall.');
        }

        $requirementDefinitions = RequirementType::officialChecklistMapByName();
        $requirementTypes = RequirementType::whereIn('requirement_name', array_keys($requirementDefinitions))
            ->get()
            ->sortBy(function (RequirementType $requirementType) use ($requirementDefinitions) {
                return $requirementDefinitions[$requirementType->requirement_name]['sort_order'] ?? PHP_INT_MAX;
            })
            ->values();

        return view('applications.create', compact('opening', 'requirementTypes', 'requirementDefinitions'));
    }

    /**
     * Persist a new broker application and its uploaded requirements.
     */
    public function store(StoreBrokerApplicationRequest $request, ApplicationOpening $opening): RedirectResponse
    {
        $this->ensureApplicantAccess();

        $validated = $request->validated();
        $requirementDefinitions = RequirementType::officialChecklistMapByName();
        $requirementTypes = RequirementType::whereIn('requirement_name', array_keys($requirementDefinitions))
            ->get()
            ->sortBy(function (RequirementType $requirementType) use ($requirementDefinitions) {
                return $requirementDefinitions[$requirementType->requirement_name]['sort_order'] ?? PHP_INT_MAX;
            })
            ->values();

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
                $requirementPayload = $validated['requirements'][$requirementType->id] ?? [];
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

                ApplicationRequirement::create([
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
    public function show(BrokerApplication $application): View
    {
        $this->ensureApplicantAccess();

        abort_unless($application->user_id === Auth::id(), 403);

        $application->load([
            'applicationOpening.stall',
            'requirements.requirementType',
            'reviewedBy',
            'selectedBy',
            'broker.stall',
        ]);

        return view('applications.show', compact('application'));
    }

    /**
     * Keep the portal limited to applicants waiting for a broker decision.
     */
    private function ensureApplicantAccess(): void
    {
        $user = Auth::user();

        abort_if(!$user, 403);
        abort_if($user->isAdmin() || $user->isStaff() || $user->isBroker(), 403);
    }
}
