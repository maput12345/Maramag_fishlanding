<?php

namespace App\Http\Controllers;

use App\Constants\ApplicationStatusConstant;
use App\Constants\OpeningStatusConstant;
use App\Constants\RequirementVerificationStatusConstant;
use App\Http\Requests\StoreBrokerApplicationRequest;
use App\Http\Requests\UpdateBrokerApplicationRevisionRequest;
use App\Models\ApplicationOpening;
use App\Models\SubmittedRequirement;
use App\Models\BrokerApplication;
use App\Models\RequirementType;
use App\Models\User;
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

        $openings = ApplicationOpening::with(['openingBatch', 'stall.stallImages'])
            ->availableForApplication()
            ->withCount('brokerApplications')
            ->get()
            ->sortBy('start_date')
            ->values();

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
            ->whereNotIn('application_status', ApplicationStatusConstant::terminalStatuses())
            ->first();

        return view('applications.index', compact('openings', 'applications', 'primaryOpening', 'currentApplication'));
    }

    /**
     * Show the applicant's submitted applications on a dedicated page.
     */
    public function myApplications(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

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

        $applicationsCount = $applications->count();

        return view('applications.my-applications', compact('applications', 'applicationsCount'));
    }

    /**
     * Show the broker application form for a specific opening.
     */
    public function create(ApplicationOpening $opening): View|RedirectResponse
    {
        if ($redirect = $this->redirectNonApplicants()) {
            return $redirect;
        }

        if ($opening->opening_status !== OpeningStatusConstant::OPEN || !$opening->start_date || !$opening->end_date || !$opening->hasAvailableStall()) {
            return redirect()->route('applications.index')
                ->with('error', 'This stall opening is not currently accepting applications.');
        }

        if (now()->toDateString() < $opening->start_date->toDateString() || now()->toDateString() > $opening->end_date->toDateString()) {
            return redirect()->route('applications.index')
                ->with('error', 'This stall opening is already closed.');
        }

        $alreadyApplied = Auth::user()
            ->brokerApplications()
            ->whereNotIn('application_status', ApplicationStatusConstant::terminalStatuses())
            ->exists();

        if ($alreadyApplied) {
            return redirect()->route('applications.index')
                ->with('info', 'You already have an active application for the current open stalls.');
        }

        $requirementDefinitions = $opening->requirementDefinitionMap();
        $requirementTypes = $opening->resolvedRequirementTypes();
        $user = Auth::user();
        $applicantProfile = $user->applicantProfile;
        $latestApplication = $user
            ->brokerApplications()
            ->latest('submitted_at')
            ->latest('id')
            ->first();

        $opening->loadMissing('stall.stallImages');

        return view('applications.create', compact('opening', 'requirementTypes', 'requirementDefinitions', 'applicantProfile', 'latestApplication'));
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
            /** @var User $user */
            $user = Auth::user();
            $applicantType = $validated['applicant_type'];
            $isJuridicalPerson = $applicantType === RequirementType::APPLICANT_TYPE_JURIDICAL;
            $nameParts = $isJuridicalPerson
                ? $this->representativeNameParts($validated['representative_name'])
                : $this->validatedNaturalPersonNameParts($validated, $user);
            $contactNumber = $isJuridicalPerson
                ? $validated['representative_contact_number']
                : $validated['contact_number'];
            $address = $isJuridicalPerson
                ? $validated['business_address']
                : $validated['address'];

            $application = BrokerApplication::create([
                'user_id' => $user->id,
                'application_opening_id' => $opening->id,
                'opening_batch_id' => $opening->opening_batch_id,
                'applicant_type' => $applicantType,
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'last_name' => $nameParts['last_name'],
                'suffix' => $nameParts['suffix'] ?? null,
                'civil_status' => $isJuridicalPerson ? null : $validated['civil_status'],
                'spouse_name' => $isJuridicalPerson ? null : ($validated['spouse_name'] ?? null),
                'spouse_contact_number' => $isJuridicalPerson ? null : ($validated['spouse_contact_number'] ?? null),
                'business_name' => $validated['business_name'] ?? null,
                'business_address' => $isJuridicalPerson ? $validated['business_address'] : null,
                'representative_name' => $isJuridicalPerson ? $validated['representative_name'] : null,
                'representative_position' => $isJuridicalPerson ? $validated['representative_position'] : null,
                'address' => $address,
                'contact_number' => $contactNumber,
                'application_status' => ApplicationStatusConstant::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $user->loadMissing('applicantProfile');
            $user->updateProfile([
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'last_name' => $nameParts['last_name'],
                'suffix' => $nameParts['suffix'] ?? null,
                'contact_number' => $contactNumber,
                'address' => $address,
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
                    'verification_status' => RequirementVerificationStatusConstant::PENDING,
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

        if ($application->application_status !== ApplicationStatusConstant::NEEDS_REVISION) {
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
            $resubmittedAt = now();

            $application->update([
                'business_name' => $validated['business_name'] ?? null,
                'address' => $validated['address'],
                'contact_number' => $validated['contact_number'],
                'application_status' => ApplicationStatusConstant::SUBMITTED,
                'reviewed_by_employee_id' => null,
                'review_date' => null,
                'submitted_at' => $resubmittedAt,
                'revision_resubmitted_at' => $resubmittedAt,
                'revision_count' => ((int) $application->revision_count) + 1,
            ]);

            /** @var User $user */
            $user = Auth::user();
            $user->loadMissing('applicantProfile');
            $user->updateProfile([
                'first_name' => $application->first_name,
                'middle_name' => $application->middle_name,
                'last_name' => $application->last_name,
                'suffix' => $application->suffix,
                'contact_number' => $validated['contact_number'],
                'address' => $validated['address'],
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
                $requiresApplicantAction = $this->requirementRequiresApplicantAction($requirement->verification_status);

                if (!$requiresApplicantAction && !$hasReplacementFile) {
                    continue;
                }

                $updates = [
                    'document_number' => $requirementPayload['document_number'] ?? null,
                    'issuing_office' => $requirementPayload['issuing_office'] ?? null,
                    'issue_date' => $requirementPayload['issue_date'] ?? null,
                    'expiry_date' => $requirementPayload['expiry_date'] ?? null,
                ];

                if ($hasReplacementFile) {
                    $updates['file_path'] = $replacementFile->store('broker-applications/' . $application->id, 'public');
                    $updates['uploaded_at'] = $resubmittedAt;
                }

                if ($hasReplacementFile || $requiresApplicantAction) {
                    $updates['verification_status'] = RequirementVerificationStatusConstant::PENDING;
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

    private function requirementRequiresApplicantAction(?string $verificationStatus): bool
    {
        return in_array($verificationStatus, [
            RequirementVerificationStatusConstant::NEEDS_REVISION,
            RequirementVerificationStatusConstant::REJECTED,
        ], true);
    }

    private function applicantProfileNameParts(User $user): array
    {
        $latestApplication = $user->latestBrokerApplication;
        $applicantProfile = $user->applicantProfile;
        $fallbackNameParts = User::splitName($applicantProfile?->name ?: ($latestApplication?->name ?: $user->name));
        $nameParts = User::extractNameParts([
            'first_name' => $applicantProfile?->first_name ?? $latestApplication?->first_name,
            'middle_name' => $applicantProfile?->middle_name ?? $latestApplication?->middle_name,
            'last_name' => $applicantProfile?->last_name ?? $latestApplication?->last_name,
        ], $fallbackNameParts);

        $nameParts['first_name'] = $nameParts['first_name'] ?: ($fallbackNameParts['first_name'] ?? 'Applicant');
        $nameParts['last_name'] = $nameParts['last_name'] ?: ($fallbackNameParts['last_name'] ?? $nameParts['first_name']);

        return $nameParts;
    }

    private function validatedNaturalPersonNameParts(array $validated, User $user): array
    {
        $fallbackNameParts = $this->applicantProfileNameParts($user);

        return [
            'first_name' => trim((string) ($validated['first_name'] ?? $fallbackNameParts['first_name'])),
            'middle_name' => $this->nullableApplicationText($validated['middle_name'] ?? $fallbackNameParts['middle_name'] ?? null),
            'last_name' => trim((string) ($validated['last_name'] ?? $fallbackNameParts['last_name'])),
            'suffix' => $this->nullableApplicationText($validated['suffix'] ?? $user->applicantProfile?->suffix ?? $user->latestBrokerApplication?->suffix),
        ];
    }

    private function representativeNameParts(string $representativeName): array
    {
        $nameParts = User::splitName($representativeName);

        return [
            'first_name' => $nameParts['first_name'] ?: 'Representative',
            'middle_name' => $this->nullableApplicationText($nameParts['middle_name'] ?? null),
            'last_name' => $nameParts['last_name'] ?: $nameParts['first_name'] ?: 'Representative',
            'suffix' => null,
        ];
    }

    private function nullableApplicationText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
