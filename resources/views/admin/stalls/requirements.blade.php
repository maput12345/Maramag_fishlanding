@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    @if(session('success') || session('error') || session('info'))
        <section class="space-y-3">
            @if(session('success'))
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800 shadow-sm">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800 shadow-sm">{{ session('error') }}</div>
            @endif

            @if(session('info'))
                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-800 shadow-sm">{{ session('info') }}</div>
            @endif
        </section>
    @endif

    @if($errors->any())
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800 shadow-sm">
            <p class="font-semibold">Requirement changes could not be saved.</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Add Requirement</h2>
            </div>
            <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">{{ $requirementTypes->count() }} requirements</span>
        </div>

        <form action="{{ route('admin.stalls.requirements.store') }}" method="POST" class="mt-6 grid gap-4 lg:grid-cols-[1fr,0.8fr,0.7fr]">
            @csrf
            <div>
                <label for="requirement_name" class="block text-sm font-medium text-slate-700">Requirement Name</label>
                <input id="requirement_name" name="requirement_name" type="text" value="{{ old('requirement_name') }}" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Example: Police Clearance" required>
            </div>
            <div>
                <label for="audience" class="block text-sm font-medium text-slate-700">Applies To</label>
                <select id="audience" name="audience" class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    @foreach([
                        \App\Models\RequirementType::APPLICANT_TYPE_BOTH => 'All Applicants',
                        \App\Models\RequirementType::APPLICANT_TYPE_NATURAL => 'Natural Person',
                        \App\Models\RequirementType::APPLICANT_TYPE_JURIDICAL => 'Juridical Person',
                    ] as $audienceValue => $audienceLabel)
                        <option value="{{ $audienceValue }}" @selected(old('audience', \App\Models\RequirementType::APPLICANT_TYPE_BOTH) === $audienceValue)>{{ $audienceLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <label class="flex min-h-[3rem] flex-1 items-center gap-3 rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700">
                    <input name="is_required" type="checkbox" value="1" class="rounded border-slate-300 text-slate-900" @checked(old('is_required', '1'))>
                    Required by default
                </label>
                <button type="submit" class="app-button app-button--dark">Add</button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Requirements List</h2>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Requirement</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Applies To</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Default</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($requirementTypes as $requirementType)
                        <tr>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ $requirementType->requirement_name }}</td>
                            <td class="px-4 py-4">{{ \App\Models\RequirementType::applicantTypeLabel($requirementType->audience ?: \App\Models\RequirementType::APPLICANT_TYPE_BOTH) }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $requirementType->is_required ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                                    {{ $requirementType->is_required ? 'Required' : 'Optional' }}
                                </span>
                            </td>
                            <td class="max-w-md px-4 py-4 text-slate-600">{{ $requirementType->description ?: 'No description recorded.' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No requirements created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
