@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <a href="{{ route('admin.applications.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">← Back to applications</a>
        <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-blue-600">Application Review</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $application->name }}</h1>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $application->applicationOpening?->stall?->display_name ?? 'Stall opening' }} · submitted {{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}
                </p>
            </div>
            <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $application->application_status }}</span>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.25fr,0.95fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Applicant Information</h2>
            <dl class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Full Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Email</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->user?->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Business Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->business_name ?: 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Contact Number</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->contact_number }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Address</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Reviewed By</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->reviewedBy?->name ?? 'Pending' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Selected By</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->selectedBy?->name ?? 'Pending' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Winner Selection</h2>
            @if($application->broker)
                <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">
                    <p class="font-semibold">Broker profile already created.</p>
                    <p class="mt-1">Assigned to {{ $application->broker->stall?->display_name ?? 'the selected stall' }}.</p>
                </div>
            @elseif($application->application_status === 'Qualified')
                <p class="mt-4 text-sm text-slate-600">Once the offline bidding decision is final, record the winner here and the system will activate the broker account automatically.</p>
                <form action="{{ route('admin.applications.winner', $application) }}" method="POST" class="mt-6">
                    @csrf
                    <button type="submit" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Confirm Winner and Activate Broker
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                    This application must be marked <span class="font-semibold text-slate-900">Qualified</span> before it can be selected as the winner.
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Requirement Verification</h2>
        <form action="{{ route('admin.applications.review', $application) }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PATCH')

            @foreach($application->requirements as $requirement)
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $requirement->requirementType?->requirement_name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Uploaded {{ optional($requirement->uploaded_at)->format('M d, Y h:i A') ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($requirement->file_url)
                                <a href="{{ $requirement->file_url }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white">
                                    View File
                                </a>
                            @endif
                            <input type="hidden" name="requirements[{{ $loop->index }}][id]" value="{{ $requirement->id }}">
                            <select name="requirements[{{ $loop->index }}][verification_status]" class="rounded-full border border-slate-300 px-4 py-2 text-sm">
                                @foreach(['Pending', 'Verified', 'Rejected'] as $verificationStatus)
                                    <option value="{{ $verificationStatus }}" {{ $requirement->verification_status === $verificationStatus ? 'selected' : '' }}>
                                        {{ $verificationStatus }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Document Number</label>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">{{ $requirement->document_number ?: 'N/A' }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Issuing Office</label>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">{{ $requirement->issuing_office ?: 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700">Requirement Remarks</label>
                        <textarea name="requirements[{{ $loop->index }}][remarks]" rows="2" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('requirements.' . $loop->index . '.remarks', $requirement->remarks) }}</textarea>
                    </div>
                </article>
            @endforeach

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="application_status" class="block text-sm font-medium text-slate-700">Overall Application Status</label>
                    <select id="application_status" name="application_status" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        @foreach(['Under Review', 'Needs Revision', 'Rejected', 'Qualified'] as $reviewStatus)
                            <option value="{{ $reviewStatus }}" {{ $application->application_status === $reviewStatus ? 'selected' : '' }}>
                                {{ $reviewStatus }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="remarks" class="block text-sm font-medium text-slate-700">LEEO Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">{{ old('remarks', $application->remarks) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Save Review
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
