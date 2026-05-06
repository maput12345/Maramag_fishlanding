<!-- Applicant Archive Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-users class="w-4 h-4 md:w-5 md:h-5 text-blue-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Applicant Accounts</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['totalApplicants'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-check-circle class="w-4 h-4 md:w-5 md:h-5 text-green-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Active Applicant Logins</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['activeApplicants'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-archive-box class="w-4 h-4 md:w-5 md:h-5 text-amber-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Archived Applicant Logins</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['archivedApplicants'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">
        <div class="flex items-center">
            <div class="w-6 h-6 md:w-8 md:h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                <x-heroicon-o-document-text class="w-4 h-4 md:w-5 md:h-5 text-slate-600" />
            </div>
            <div class="ml-3 md:ml-4">
                <p class="text-xs md:text-sm font-medium text-gray-600">Not Selected Records</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $count['notSelectedApplications'] }}</p>
            </div>
        </div>
    </div>
</div>

<div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
    <p class="font-semibold">Archive policy</p>
    <p class="mt-1">When all stalls in a selection round are awarded, non-winner applicant-only accounts are deactivated here. Their application records stay available for LEEO history and audit.</p>
</div>

<!-- Applicant Archive List -->
<div class="space-y-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Applicant Archive</h3>
            <span class="text-sm text-gray-500">{{ $applicants->count() }} result{{ $applicants->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Application</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account State</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($applicants as $applicant)
                        @php
                            $latestApplication = $applicant->brokerApplications->first();
                            $displayName = $latestApplication?->name ?: $applicant->name;
                            $applicationStatus = $latestApplication?->application_status ?? 'No Application';
                            $accountIsArchived = $applicant->status === \App\Constants\UserStatusConstant::DEACTIVATED;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-slate-700 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                        <span>{{ substr($displayName, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $displayName }}</div>
                                        <div class="text-sm text-gray-500">{{ $applicant->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-status-badge :status="$applicationStatus" />
                                @if($latestApplication?->selectedStall)
                                    <div class="mt-1 text-xs text-gray-500">{{ $latestApplication->selectedStall->display_name }}</div>
                                @elseif($latestApplication?->applicationOpening?->stall)
                                    <div class="mt-1 text-xs text-gray-500">{{ $latestApplication->applicationOpening->stall->display_name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-status-badge :status="$accountIsArchived ? 'Archived' : 'Active Login'" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ optional($latestApplication?->submitted_at)->format('M d, Y') ?? $applicant->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    @if($latestApplication)
                                        <a href="{{ route('admin.applications.show', $latestApplication) }}" class="app-button app-button--primary px-3 py-2 text-xs">
                                            View Application
                                        </a>
                                    @endif

                                    @if($applicant->status === \App\Constants\UserStatusConstant::ACTIVE)
                                        <form method="POST" action="{{ route('admin.users.deactivate', $applicant) }}" class="inline" data-swal="deactivate">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="app-button app-button--danger px-3 py-2 text-xs">
                                                Archive Login
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.activate', $applicant) }}" class="inline" data-swal="activate">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="app-button app-button--success px-3 py-2 text-xs">
                                                Reactivate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No applicant accounts matched the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
