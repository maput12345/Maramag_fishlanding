@php
    use Carbon\Carbon;

    $breadcrumbs = [
        ['title' => 'Financial Statement'],
    ];

    $topbarAction = [
        'label' => 'Transaction',
        'url' => route('broker.sales.sales', ['modal' => 'create']),
        'modal' => false,
    ];

    $statementDateCarbon = Carbon::parse($statementDate);
    $previousDate = $statementDateCarbon->copy()->subDay()->toDateString();
    $nextDate = $statementDateCarbon->copy()->addDay()->toDateString();
    $todayDate = now()->toDateString();
    $manualAdjustmentsTotal = $statement['selling_general_and_administrative_expenses'] + $statement['loss_on_sale'];
    $printReceiptScriptUrl = asset('js/print-receipt.js') . '?v=' . filemtime(public_path('js/print-receipt.js'));
@endphp

@extends('layouts.broker')

@section('content')
<div class="w-full dashboard-shell space-y-6">
    <section class="overflow-hidden rounded-[28px] bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 text-white shadow-xl">
        <div class="grid gap-8 px-6 py-7 lg:grid-cols-[1.4fr,0.8fr] lg:px-8">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">
                    Daily Finance
                </span>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight">Income And Expense</h1>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/70">Statement Date</p>
                        <p class="mt-2 text-lg font-semibold">{{ $statementDateCarbon->format('F d, Y') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/70">Sales Slips</p>
                        <p class="mt-2 text-lg font-semibold">{{ number_format($statement['sales_count']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.18em] text-blue-100/70">Sold Fish Boxes</p>
                        <p class="mt-2 text-lg font-semibold">{{ number_format($statement['sold_boxes']) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[24px] border border-white/10 bg-white/10 p-5 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-100/70">Daily Snapshot</p>
                <dl class="mt-4 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <dt class="text-sm text-blue-100/80">Sales Revenue</dt>
                            <dd class="text-xs text-blue-100/60">Recorded sale totals for the day</dd>
                        </div>
                        <div class="text-right text-lg font-semibold">PHP {{ number_format($statement['sales'], 2) }}</div>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <dt class="text-sm text-blue-100/80">Gross Profit</dt>
                            <dd class="text-xs text-blue-100/60">Sales less direct fish cost</dd>
                        </div>
                        <div class="text-right text-lg font-semibold">PHP {{ number_format($statement['gross_profit'], 2) }}</div>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <dt class="text-sm text-blue-100/80">Collections</dt>
                            <dd class="text-xs text-blue-100/60">Cash received on the day</dd>
                        </div>
                        <div class="text-right text-lg font-semibold">PHP {{ number_format($statement['collections'], 2) }}</div>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-t border-white/10 pt-4">
                        <div>
                            <dt class="text-sm text-blue-100/80">Net Income</dt>
                            <dd class="text-xs text-blue-100/60">After expenses and loss adjustments</dd>
                        </div>
                        <div class="text-right text-2xl font-semibold">PHP {{ number_format($statement['net_income'], 2) }}</div>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="panel-card">
        <div class="panel-card__inner">
            <div class="panel-card__header">
                <div>
                    <h3 class="panel-card__title">Daily Filter</h3>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form method="GET" action="{{ route('broker.financial-statements.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label for="statement_date" class="mb-1 block text-sm font-medium text-gray-700">Statement Date</label>
                        <input
                            id="statement_date"
                            type="date"
                            name="statement_date"
                            value="{{ $statementDate }}"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 sm:min-w-[220px]"
                        >
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('broker.financial-statements.index', ['statement_date' => $todayDate]) }}"
                           class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100">
                            Today
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                            View Statement
                        </button>
                    </div>
                </form>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('broker.financial-statements.index', ['statement_date' => $previousDate]) }}"
                       class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                        Previous Day
                    </a>
                    <a href="{{ route('broker.financial-statements.index', ['statement_date' => $nextDate]) }}"
                       class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                        Next Day
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr,0.8fr]">
        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Income Statement</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                onclick="printFinancialStatement()"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                title="Print Income Statement"
                                aria-label="Print Income Statement">
                            <x-heroicon-o-printer class="h-4 w-4" />
                        </button>
                        <span class="panel-card__hint">{{ $statementDateCarbon->format('M d, Y') }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-start justify-between rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-emerald-900">Sales</p>
                        </div>
                        <p class="text-lg font-semibold text-emerald-900">PHP {{ number_format($statement['sales'], 2) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-rose-100 bg-rose-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-rose-900">Less: Cost of Sales</p>
                        </div>
                        <p class="text-lg font-semibold text-rose-900">PHP {{ number_format($statement['cost_of_sales'], 2) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-blue-100 bg-blue-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Gross Profit</p>

                        </div>
                        <p class="text-lg font-semibold text-blue-900">PHP {{ number_format($statement['gross_profit'], 2) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-amber-900">Less: Selling, General and Administrative Expenses</p>
                        </div>
                        <p class="text-lg font-semibold text-amber-900">PHP {{ number_format($statement['selling_general_and_administrative_expenses'], 2) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-indigo-900">Operating Income</p>
                        </div>
                        <p class="text-lg font-semibold text-indigo-900">PHP {{ number_format($statement['operating_income'], 2) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-fuchsia-100 bg-fuchsia-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-fuchsia-900">Less: Loss on Sale</p>
                        </div>
                        <p class="text-lg font-semibold text-fuchsia-900">PHP {{ number_format($statement['loss_on_sale'], 2) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl bg-slate-900 px-4 py-4 text-white">
                        <div>
                            <p class="text-sm font-semibold">Net Income</p>
                        </div>
                        <p class="text-xl font-semibold">PHP {{ number_format($statement['net_income'], 2) }}</p>
                    </div>
                </div>

                <div class="mt-5 border-t border-gray-200 pt-5">
                    <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-orange-900">Outstanding Receivable Balance</p>
                                <p class="mt-1 text-xs text-orange-700/80">As of {{ $statementDateCarbon->format('M d, Y') }}</p>
                            </div>
                            <p class="text-lg font-semibold text-orange-900">PHP {{ number_format($statement['outstanding_receivable_balance'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Add Daily Expenses</h3>
                    </div>
                </div>

                @if($errors->any())
                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="font-semibold">Please check the form.</p>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('broker.financial-statements.entries.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="form_statement_date" class="mb-1 block text-sm font-medium text-gray-700">Statement Date</label>
                        <input
                            id="form_statement_date"
                            type="date"
                            name="statement_date"
                            value="{{ old('statement_date', $statementDate) }}"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>

                    <div>
                        <label for="entry_type" class="mb-1 block text-sm font-medium text-gray-700">Statement Line</label>
                        <select
                            id="entry_type"
                            name="entry_type"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Select statement line</option>
                            @foreach($entryTypeOptions as $type => $label)
                                <option value="{{ $type }}" {{ old('entry_type') === $type ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <input
                            id="description"
                            type="text"
                            name="description"
                            value="{{ old('description') }}"
                            placeholder="Example: Helpers' allowance, hauling fee, damaged stock adjustment"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            required
                        >
                    </div>

                    <div>
                        <label for="amount" class="mb-1 block text-sm font-medium text-gray-700">Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-gray-500">PHP</span>
                            <input
                                id="amount"
                                type="number"
                                name="amount"
                                value="{{ old('amount') }}"
                                min="0.01"
                                step="0.01"
                                class="w-full rounded-xl border border-gray-300 py-3 pl-14 pr-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                        Save Daily Expense
                    </button>
                </form>
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr,0.85fr]">
        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Daily Sales Support</h3>
                    </div>
                    <span class="panel-card__hint">{{ $salesBreakdown->count() }} sales rows</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sale</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Buyer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Boxes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sales</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cost</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Gross Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($salesBreakdown as $sale)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ $sale['formatted_id'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $sale['sales_date'] }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ $sale['buyer_name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $sale['commodities'] ?: 'No commodity label' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">{{ number_format($sale['sold_boxes']) }}</td>
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900">PHP {{ number_format($sale['sales'], 2) }}</td>
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900">PHP {{ number_format($sale['cost_of_sales'], 2) }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-blue-700">PHP {{ number_format($sale['gross_profit'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12">
                                        <div class="empty-state">
                                            <x-heroicon-o-document-text class="heroicon" />
                                            <p class="text-sm">No sales were recorded for this business day.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-card__inner">
                <div class="panel-card__header">
                    <div>
                        <h3 class="panel-card__title">Expenses</h3>
                    </div>
                    <span class="panel-card__hint">{{ number_format(collect($entryGroups)->sum('entries_count')) }} entries</span>
                </div>

                <div class="space-y-4">
                    @foreach($entryGroups as $group)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $group['label'] }}</h4>
                                    <p class="text-xs text-gray-500">{{ $group['entries_count'] }} item{{ $group['entries_count'] === 1 ? '' : 's' }}</p>
                                </div>
                                <div class="text-sm font-semibold text-gray-900">PHP {{ number_format($group['total'], 2) }}</div>
                            </div>

                            <div class="mt-3 space-y-3">
                                @forelse($group['entries'] as $entry)
                                    <div class="rounded-xl border border-white bg-white px-3 py-3 shadow-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $entry->description }}</p>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    Recorded {{ optional($entry->created_at)->format('M d, Y h:i A') }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-semibold text-gray-900">PHP {{ number_format((float) $entry->amount, 2) }}</p>
                                                <form method="POST"
                                                      action="{{ route('broker.financial-statements.entries.destroy', ['entry' => $entry, 'statement_date' => $statementDate]) }}"
                                                      class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-medium text-red-600 transition-colors hover:text-red-700">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-3 py-5 text-center text-sm text-gray-500">
                                        No entries recorded for this line yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div
        id="financial-statement-print-content"
        data-watermark-logo-url="{{ asset('image/logo.png') }}"
        class="hidden"
    >
        <div class="mx-auto max-w-md bg-white">
            <div class="mb-4 border-b border-gray-200 pb-4 text-center">
                <h1 class="text-2xl font-bold text-gray-900">{{ auth()->user()->name }}</h1>
                @if(optional(auth()->user()->broker)->stall_name)
                    <p class="text-sm text-gray-600">{{ auth()->user()->broker->stall_name }}</p>
                @endif
                <p class="text-xs text-gray-500">Income Statement</p>
            </div>

            <div class="mb-4">
                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-600">Statement Date:</span>
                    <span class="font-medium">{{ $statementDateCarbon->format('M d, Y') }}</span>
                </div>
                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-600">Sales Slips:</span>
                    <span class="font-medium">{{ number_format($statement['sales_count']) }}</span>
                </div>
                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-600">Sold Fish Boxes:</span>
                    <span class="font-medium">{{ number_format($statement['sold_boxes']) }}</span>
                </div>
                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-600">Collections:</span>
                    <span class="font-medium">PHP {{ number_format($statement['collections'], 2) }}</span>
                </div>
            </div>

            <div class="mb-4 border-t border-gray-200 pt-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Income Statement</h3>
                <div class="space-y-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Sales</span>
                            <span class="font-semibold text-gray-900">PHP {{ number_format($statement['sales'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Cost of Sales</span>
                            <span class="font-semibold text-gray-900">PHP {{ number_format($statement['cost_of_sales'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Gross Profit</span>
                            <span class="font-semibold text-gray-900">PHP {{ number_format($statement['gross_profit'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Selling, General and Administrative Expenses</span>
                            <span class="font-semibold text-gray-900">PHP {{ number_format($statement['selling_general_and_administrative_expenses'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Operating Income</span>
                            <span class="font-semibold text-gray-900">PHP {{ number_format($statement['operating_income'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Loss on Sale</span>
                            <span class="font-semibold text-gray-900">PHP {{ number_format($statement['loss_on_sale'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 border-t border-gray-200 pt-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Manual Adjustments Total:</span>
                        <span class="font-semibold">PHP {{ number_format($manualAdjustmentsTotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 text-sm">
                        <span class="font-semibold text-gray-600">Net Income:</span>
                        <span class="font-bold text-green-600">PHP {{ number_format($statement['net_income'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mb-4 border-t border-gray-200 pt-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Outstanding Receivable Balance</h3>
                <div class="rounded-lg bg-orange-50 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-orange-900">As of {{ $statementDateCarbon->format('M d, Y') }}</span>
                        <span class="font-semibold text-orange-900">PHP {{ number_format($statement['outstanding_receivable_balance'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4 text-center">
                <p class="text-xs text-gray-500">Generated on {{ now()->format('M d, Y g:i A') }}</p>
            </div>
        </div>
    </div>
</div>

<script src="{{ $printReceiptScriptUrl }}" defer></script>
<script>
    function printFinancialStatement() {
        window.printReceipt(
            'financial-statement-print-content',
            'Income Statement - {{ $statementDateCarbon->format('Y-m-d') }}'
        );
    }
</script>
@endsection
