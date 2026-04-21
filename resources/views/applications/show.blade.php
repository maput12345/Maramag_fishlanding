@extends('layouts.app')

@section('body-class', 'login-shell theme-admin')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <a href="{{ route('applications.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">← Back to application portal</a>
        <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-blue-600">Application Record</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $application->applicationOpening?->stall?->display_name ?? 'Stall Opening' }}</h1>
                <p class="mt-2 text-sm text-slate-600">Submitted on {{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $application->application_status }}</span>
        </div>
    </div>

    <section class="mt-8 grid gap-6 lg:grid-cols-[1.4fr,1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Application Details</h2>
            <dl class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Business Name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->business_name ?: 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Contact Number</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->contact_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Review Date</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ optional($application->review_date)->format('M d, Y h:i A') ?? 'Pending' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Address</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->address }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Remarks</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $application->remarks ?: 'No review remarks yet.' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Review Timeline</h2>
            <div class="mt-6 space-y-4 text-sm text-slate-600">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">Reviewed By</p>
                    <p class="mt-1">{{ $application->reviewedBy?->name ?? 'Waiting for LEEO review' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900">Winner Selection</p>
                    <p class="mt-1">{{ $application->selectedBy?->name ?? 'Not yet selected' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ optional($application->selected_at)->format('M d, Y h:i A') ?? 'Pending offline bidding result' }}</p>
                </div>
                @if($application->broker)
                    <div class="rounded-2xl bg-emerald-50 p-4 text-emerald-800">
                        <p class="font-semibold">Broker account activated</p>
                        <p class="mt-1">Assigned to {{ $application->broker->stall?->display_name ?? 'the winning stall' }}.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Submitted Requirements</h2>
        <div class="mt-6 space-y-4">
            @foreach($application->requirements as $requirement)
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $requirement->requirementType?->requirement_name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">Verification status: {{ $requirement->verification_status }}</p>
                        </div>
                        @if($requirement->file_url)
                            <a href="{{ $requirement->file_url }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white">
                                View File
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
