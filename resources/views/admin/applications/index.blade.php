@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Submitted Applications</h2>
            </div>
            <form action="{{ route('admin.applications.index') }}" method="GET" class="flex items-center gap-2">
                <select name="status" class="rounded-full border border-slate-300 px-4 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach(['Submitted', 'Under Review', 'Needs Revision', 'Rejected', 'Qualified', 'Winner', 'Not Selected'] as $applicationStatus)
                        <option value="{{ $applicationStatus }}" {{ $status === $applicationStatus ? 'selected' : '' }}>{{ $applicationStatus }}</option>
                    @endforeach
                </select>
                <button type="submit" class="app-button app-button--secondary">Filter</button>
            </form>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applicant</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Submitted</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($applications as $application)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $application->name }}</div>
                                <div class="text-xs text-slate-500">{{ $application->user?->email }}</div>
                            </td>
                            <td class="px-4 py-4">{{ $application->application_status }}</td>
                            <td class="px-4 py-4">{{ optional($application->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.applications.show', $application) }}" class="app-button app-button--secondary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No applications found for the selected filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $applications->withQueryString()->links() }}
        </div>
    </section>
</div>
@endsection
