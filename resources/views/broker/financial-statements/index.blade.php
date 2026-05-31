@php
use Carbon\Carbon;

    $breadcrumbs = [
        ['title' => 'Financial Statement'],
    ];

    $statementDateCarbon = Carbon::parse($statementDate);
    $previousDate = $statementDateCarbon->copy()->subDay()->toDateString();
    $nextDate = $statementDateCarbon->copy()->addDay()->toDateString();
    $todayDate = now()->toDateString();
    $dailyExpensesTotal = $statement['selling_general_and_administrative_expenses'] + $statement['loss_on_sale'];
    $printReceiptScriptUrl = asset('js/print-receipt.js') . '?v=' . filemtime(public_path('js/print-receipt.js'));
    $peso = fn ($amount) => ((float) $amount < 0 ? '-₱' : '₱') . number_format(abs((float) $amount), 2);
    $netIncomeIsLoss = (float) $statement['net_income'] < 0;
    $operatingIncomeIsLoss = (float) $statement['operating_income'] < 0;
    $operatingIncomeCardStyle = $operatingIncomeIsLoss
        ? 'background-color: #fef2f2; border: 1px solid #fecaca;'
        : 'background-color: #eef2ff; border: 1px solid #c7d2fe;';
    $operatingIncomeTextStyle = $operatingIncomeIsLoss ? 'color: #7f1d1d;' : 'color: #312e81;';
    $netIncomeCardStyle = $netIncomeIsLoss
        ? 'background-color: #7f1d1d; border: 1px solid #991b1b; color: #ffffff;'
        : 'background-color: #0f172a; border: 1px solid #0f172a; color: #ffffff;';
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
                    <div class="filter-action-group">
                        <a href="{{ route('broker.financial-statements.index', ['statement_date' => $todayDate]) }}"
                           class="btn-clear">
                            Today
                        </a>
                        <button type="submit"
                                class="btn-search">
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

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[1.2fr,0.8fr]">
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
                            <p class="text-sm font-semibold text-emerald-900">Gross Sales</p>
                            <span class="sr-only">Sales Revenue</span>
                        </div>
                        <p class="text-lg font-semibold text-emerald-900">{{ $peso($statement['gross_sales']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-orange-100 bg-orange-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-orange-900">Less: Sales Discounts</p>
                        </div>
                        <p class="text-lg font-semibold text-orange-900">{{ $peso($statement['sales_discounts']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-emerald-900">Net Sales</p>
                        </div>
                        <p class="text-lg font-semibold text-emerald-900">{{ $peso($statement['net_sales']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-rose-100 bg-rose-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-rose-900">Less: Cost of Sales</p>
                        </div>
                        <p class="text-lg font-semibold text-rose-900">{{ $peso($statement['cost_of_sales']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-blue-100 bg-blue-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">Gross Profit</p>

                        </div>
                        <p class="text-lg font-semibold text-blue-900">{{ $peso($statement['gross_profit']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-amber-900">Less: Selling, General and Administrative Expenses</p>
                        </div>
                        <p class="text-lg font-semibold text-amber-900">{{ $peso($statement['selling_general_and_administrative_expenses']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl px-4 py-4" style="{{ $operatingIncomeCardStyle }}">
                        <div>
                            <p class="text-sm font-semibold" style="{{ $operatingIncomeTextStyle }}">Operating Income</p>
                        </div>
                        <p class="text-lg font-semibold" style="{{ $operatingIncomeTextStyle }}">{{ $peso($statement['operating_income']) }}</p>
                    </div>

                    <div class="flex items-start justify-between rounded-2xl border border-fuchsia-100 bg-fuchsia-50 px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-fuchsia-900">Less: Loss on Sale</p>
                        </div>
                        <p class="text-lg font-semibold text-fuchsia-900">{{ $peso($statement['loss_on_sale']) }}</p>
                    </div>

                    <div class="flex items-start justify-between gap-4 rounded-2xl px-4 py-4" style="{{ $netIncomeCardStyle }}">
                        <div>
                            <p class="text-sm font-semibold" style="color: #ffffff;">Net Income</p>
                        </div>
                        <span
                            class="shrink-0 text-right text-xl font-semibold tabular-nums"
                            style="display: block; min-width: max-content; color: #ffffff !important; opacity: 1 !important; visibility: visible !important;"
                        >{{ $peso($statement['net_income']) }}</span>
                    </div>
                </div>

                <div class="mt-5 border-t border-gray-200 pt-5">
                    <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-orange-900">Outstanding Receivable Balance</p>
                                <p class="mt-1 text-xs text-orange-700/80">As of {{ $statementDateCarbon->format('M d, Y') }}</p>
                            </div>
                            <p class="text-lg font-semibold text-orange-900">{{ $peso($statement['outstanding_receivable_balance']) }}</p>
                        </div>
                        <div class="mt-4 space-y-3 border-t border-orange-200 pt-4">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="text-orange-800">Collections from Today's Sales</span>
                                <span class="font-semibold tabular-nums text-orange-900">{{ $peso($statement['collections_from_today_sales']) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="text-orange-800">Collections from Previous Balances</span>
                                <span class="font-semibold tabular-nums text-orange-900">{{ $peso($statement['collections_from_previous_balances']) }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-t border-orange-200 pt-3">
                                <span class="text-sm font-semibold text-orange-900">Cash on Hand</span>
                                <span class="text-lg font-semibold text-orange-900">{{ $peso($statement['cash_on_hand']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="space-y-6">
            <section class="panel-card">
                <div class="panel-card__inner">
                    <div class="panel-card__header">
                        <div>
                            <h3 class="panel-card__title">Add Expense</h3>
                        </div>
                        <span class="panel-card__hint">{{ $statementDateCarbon->format('M d, Y') }}</span>
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

                    <form
                        method="POST"
                        action="{{ route('broker.financial-statements.entries.store') }}"
                        class="space-y-4"
                        data-daily-expenses-form
                        data-next-other-index="{{ count($expenseCategoryOptions) }}"
                    >
                        @csrf

                        <div class="grid gap-3 sm:grid-cols-2 sm:items-end">
                            <div>
                                <label for="form_statement_date" class="mb-1 block text-sm font-medium text-gray-700">Date</label>
                                <input
                                    id="form_statement_date"
                                    type="date"
                                    name="statement_date"
                                    value="{{ old('statement_date', $statementDate) }}"
                                    class="h-12 w-full rounded-xl border border-gray-300 px-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                            </div>

                            <div>
                                <label for="entry_form_mode" class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                                <select
                                    id="entry_form_mode"
                                    name="entry_form_mode"
                                    class="h-12 w-full rounded-xl border border-gray-300 px-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    data-entry-form-mode
                                >
                                    <option value="expenses" {{ old('entry_form_mode', 'expenses') === 'expenses' ? 'selected' : '' }}>Expenses</option>
                                    <option value="loss_on_sale" {{ old('entry_form_mode') === 'loss_on_sale' ? 'selected' : '' }}>Loss on Sale</option>
                                </select>
                            </div>
                        </div>

                        <div data-expenses-panel>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <h4 class="text-sm font-semibold text-gray-900">Daily Expenses</h4>
                                <button
                                    type="button"
                                    class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                    data-add-other-expense
                                >
                                    Add Other
                                </button>
                            </div>

                            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <div class="divide-y divide-gray-100"
                                     style="max-height: 15.25rem; overflow-y: auto;"
                                     data-expense-rows>
                                <div class="sticky top-0 z-10 grid items-center gap-3 border-b border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500"
                                     style="grid-template-columns: minmax(0, 1fr) 128px;">
                                    <span>Expense</span>
                                    <span class="text-right">Amount</span>
                                </div>
                                @foreach($expenseCategoryOptions as $category => $label)
                                    @continue($category === \App\Models\FinancialStatementEntry::EXPENSE_CATEGORY_OTHER)

                                    <div class="grid items-center gap-3 px-3 py-2"
                                         style="grid-template-columns: minmax(0, 1fr) 128px;">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-900">{{ $label }}</p>
                                            <input type="hidden" name="expenses[{{ $loop->index }}][category]" value="{{ $category }}">
                                        </div>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-500">₱</span>
                                            <input
                                                type="number"
                                                name="expenses[{{ $loop->index }}][amount]"
                                                value="{{ old("expenses.{$loop->index}.amount") }}"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="h-9 w-full rounded-lg border border-gray-300 py-1.5 pl-7 pr-3 text-right text-sm font-medium tabular-nums focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                            >
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        </div>

                        <div data-loss-panel>
                            <div class="space-y-3">
                                <div>
                                    <label for="loss_description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                                    <input
                                        id="loss_description"
                                        type="text"
                                        name="loss_description"
                                        value="{{ old('loss_description') }}"
                                        placeholder="Example: Spoiled fish, damaged stock"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    >
                                </div>
                                <div>
                                    <label for="loss_amount" class="mb-1 block text-sm font-medium text-gray-700">Amount</label>
                                    <div class="currency-input-group">
                                        <span class="currency-input-symbol">₱</span>
                                        <input
                                            id="loss_amount"
                                            type="number"
                                            name="loss_amount"
                                            value="{{ old('loss_amount') }}"
                                            min="0.01"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="currency-input-field"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        @php
                            $oldOtherExpenses = collect(old('expenses', []))
                                ->filter(fn ($expense) => ($expense['category'] ?? null) === \App\Models\FinancialStatementEntry::EXPENSE_CATEGORY_OTHER)
                                ->values();
                        @endphp
                        @if($oldOtherExpenses->isNotEmpty())
                            <div class="hidden" data-old-other-expenses>
                                @foreach($oldOtherExpenses as $index => $expense)
                                    <div data-old-other-expense
                                         data-index="{{ count($expenseCategoryOptions) + $index }}"
                                         data-description="{{ $expense['description'] ?? '' }}"
                                         data-amount="{{ $expense['amount'] ?? '' }}"></div>
                                @endforeach
                            </div>
                        @endif

                        <template data-other-expense-template>
                            <div class="grid items-center gap-3 px-3 py-2"
                                 style="grid-template-columns: minmax(0, 1fr) 128px 32px;"
                                 data-other-expense-row>
                                <div class="min-w-0">
                                    <input type="hidden" data-other-category-name value="{{ \App\Models\FinancialStatementEntry::EXPENSE_CATEGORY_OTHER }}">
                                    <input
                                        type="text"
                                        data-other-description
                                        placeholder="Describe other expense"
                                        class="h-9 w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    >
                                </div>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-500">₱</span>
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="h-9 w-full rounded-lg border border-gray-300 py-1.5 pl-7 pr-3 text-right text-sm font-medium tabular-nums focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        data-other-amount
                                    >
                                </div>
                                <input
                                    type="hidden"
                                    data-other-category
                                    value="{{ \App\Models\FinancialStatementEntry::EXPENSE_CATEGORY_OTHER }}"
                                >
                                <button type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                                        aria-label="Remove other expense"
                                        data-remove-other-expense>
                                    ×
                                </button>
                            </div>
                        </template>

                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                            <span data-submit-label>Save Expenses</span>
                        </button>
                    </form>
                </div>
            </section>

            <section
                class="panel-card relative z-20"
                x-data="{ openEntries: null, editingEntry: null }"
                x-effect="
                    document.documentElement.classList.toggle('modal-scroll-lock', openEntries !== null);
                    document.body.classList.toggle('modal-scroll-lock', openEntries !== null);
                "
                @keydown.escape.window="openEntries = null; editingEntry = null"
            >
                <div class="panel-card__inner">
                    <div class="panel-card__header">
                        <div>
                            <h3 class="panel-card__title">Expense Entries</h3>
                        </div>
                        <span class="panel-card__hint">{{ $peso($dailyExpensesTotal) }}</span>
                    </div>

                    <div class="space-y-4">
                        @foreach($entryGroups as $group)
                            @php
                                $entryGroupLabel = $group['type'] === \App\Models\FinancialStatementEntry::TYPE_SGA
                                    ? 'Operating Expenses'
                                    : $group['label'];
                            @endphp
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4"
                                 data-entry-group="{{ $group['type'] }}">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900">{{ $entryGroupLabel }}</h4>
                                        <p class="text-xs text-gray-500" data-entry-group-count>{{ $group['entries_count'] }} entr{{ $group['entries_count'] === 1 ? 'y' : 'ies' }}</p>
                                    </div>
                                    <div class="text-right text-sm font-semibold tabular-nums text-gray-900" data-entry-group-total>{{ $peso($group['total']) }}</div>
                                </div>

                                @if($group['entries']->isEmpty())
                                    <div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white px-3 py-5 text-center text-sm text-gray-500">
                                        No entries recorded for this line yet.
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        class="mt-3 flex w-full items-center justify-between gap-3 rounded-xl border border-white bg-white px-3 py-3 text-sm font-medium text-gray-900 shadow-sm transition-colors hover:bg-gray-50"
                                        @click="openEntries = @js($group['type']); editingEntry = null"
                                    >
                                        <span>View entries</span>
                                        <span class="text-xs text-gray-500">{{ $group['entries_count'] }} entr{{ $group['entries_count'] === 1 ? 'y' : 'ies' }}</span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <template x-teleport="body">
                    <div
                        x-cloak
                        x-show="openEntries !== null"
                        class="fixed inset-0 flex items-center justify-center px-4 py-6 backdrop-blur-sm"
                        style="display: none; z-index: 900; background-color: rgba(2, 6, 23, 0.56);"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="entries-modal-title"
                    >
                        <button
                            type="button"
                            class="absolute inset-0 cursor-default"
                            aria-label="Close entries"
                            @click="openEntries = null; editingEntry = null"
                        ></button>

                        @foreach($entryGroups as $modalGroup)
                            @if($modalGroup['entries']->isNotEmpty())
                                <div
                                    x-show="openEntries === @js($modalGroup['type'])"
                                    class="relative flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                                    style="max-height: calc(100vh - 3rem);"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    @click.stop
                                >
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-3">
                                        <div>
                                            <h3 id="entries-modal-title" class="text-lg font-semibold text-gray-900">{{ $modalGroup['label'] }} Entries</h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ $statementDateCarbon->format('M d, Y') }} ·
                                                <span data-modal-entry-count="{{ $modalGroup['type'] }}">{{ $modalGroup['entries_count'] }} item{{ $modalGroup['entries_count'] === 1 ? '' : 's' }}</span>
                                                ·
                                                <span data-modal-entry-total="{{ $modalGroup['type'] }}">₱{{ number_format($modalGroup['total'], 2) }}</span>
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            aria-label="Close entries"
                                            @click="openEntries = null; editingEntry = null"
                                        >
                                            <x-heroicon-o-x-mark class="h-5 w-5" />
                                        </button>
                                    </div>

                                    <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50 p-3">
                                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                            <div class="hidden gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 sm:grid"
                                                 style="grid-template-columns: minmax(0, 1fr) 120px 150px;">
                                                <span>Entry</span>
                                                <span class="text-right">Amount</span>
                                                <span class="text-right">Actions</span>
                                            </div>

                                            <div class="divide-y divide-gray-100" data-entry-list="{{ $modalGroup['type'] }}">
                                                @foreach($modalGroup['entries'] as $entry)
                                                            <div data-entry-row="{{ $entry->id }}" data-entry-type="{{ $modalGroup['type'] }}">
                                                        <template x-if="editingEntry === {{ $entry->id }}">
                                                            <form
                                                                method="POST"
                                                                action="{{ route('broker.financial-statements.entries.update', ['entry' => $entry]) }}"
                                                                class="bg-blue-50/60 px-4 py-3"
                                                            >
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="statement_date" value="{{ $statementDate }}">

                                                                        <div class="grid gap-3 sm:items-end"
                                                                             style="grid-template-columns: minmax(0, 1fr) 140px 128px;">
                                                                    <div>
                                                                        <label for="entry_description_{{ $entry->id }}" class="mb-1 block text-xs font-medium text-gray-600">Description</label>
                                                                        <input
                                                                            id="entry_description_{{ $entry->id }}"
                                                                            type="text"
                                                                            name="description"
                                                                            value="{{ $entry->description }}"
                                                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                                            required
                                                                        >
                                                                    </div>
                                                                    <div>
                                                                        <label for="entry_amount_{{ $entry->id }}" class="mb-1 block text-xs font-medium text-gray-600">Amount</label>
                                                                        <div class="relative">
                                                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-500">₱</span>
                                                                            <input
                                                                                id="entry_amount_{{ $entry->id }}"
                                                                                type="number"
                                                                                name="amount"
                                                                                value="{{ $entry->amount }}"
                                                                                min="0.01"
                                                                                step="0.01"
                                                                                class="h-10 w-full rounded-lg border border-gray-300 py-1.5 pl-7 pr-3 text-right text-sm font-medium tabular-nums focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                                                required
                                                                            >
                                                                        </div>
                                                                    </div>
                                                                            <div class="flex justify-end gap-2">
                                                                                <button
                                                                                    type="button"
                                                                                    class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                                                                    style="min-width: 58px;"
                                                                                    @click="editingEntry = null"
                                                                                >
                                                                                    Cancel
                                                                                </button>
                                                                                <button
                                                                                    type="submit"
                                                                                    class="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-3 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                                                                                    style="min-width: 58px;"
                                                                                >
                                                                                    Save
                                                                                </button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </template>

                                                        <template x-if="editingEntry !== {{ $entry->id }}">
                                                                    <div class="grid gap-3 px-4 py-3 sm:items-center"
                                                                         style="grid-template-columns: minmax(0, 1fr) 120px 150px;">
                                                                <div class="min-w-0">
                                                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $entry->description }}</p>
                                                                    <p class="mt-1 text-xs text-gray-500">
                                                                        Recorded {{ optional($entry->created_at)->format('M d, Y h:i A') }}
                                                                    </p>
                                                                </div>
                                                                <p class="text-left text-sm font-semibold tabular-nums text-gray-900 sm:text-right">₱{{ number_format((float) $entry->amount, 2) }}</p>
                                                                        <div class="flex justify-end gap-2">
                                                                            <button
                                                                                type="button"
                                                                                class="inline-flex h-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100"
                                                                                style="min-width: 56px;"
                                                                                @click="editingEntry = {{ $entry->id }}"
                                                                            >
                                                                                Edit
                                                                            </button>
                                                                            <form method="POST"
                                                                                  action="{{ route('broker.financial-statements.entries.destroy', ['entry' => $entry, 'statement_date' => $statementDate]) }}"
                                                                                  data-delete-entry-form
                                                                                  data-delete-entry-name="{{ $entry->description }}"
                                                                                  data-delete-entry-amount="₱{{ number_format((float) $entry->amount, 2) }}">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition-colors hover:bg-red-100"
                                                                                        style="min-width: 70px;">
                                                                                    Remove
                                                                                </button>
                                                                            </form>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="hidden px-4 py-8 text-center text-sm text-gray-500" data-entry-empty="{{ $modalGroup['type'] }}">
                                                No entries recorded for this line yet.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </template>
            </section>
        </div>
    </div>

    <section class="panel-card">
        <div class="panel-card__inner">
            <div class="panel-card__header">
                <div>
                    <h3 class="panel-card__title">Major Expenses Analytics</h3>
                    <p class="mt-1 text-sm text-gray-500">Gas and Ice/Cellophane weekly line charts for {{ $expenseAnalytics['period_label'] }}</p>
                </div>
                <span class="panel-card__hint">Bottom View</span>
            </div>

            @php
                $directionClasses = [
                    'up' => 'border-red-200 bg-red-50 text-red-700',
                    'down' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    'flat' => 'border-gray-200 bg-gray-50 text-gray-600',
                ];
                $directionLabels = [
                    'up' => 'Up',
                    'down' => 'Down',
                    'flat' => 'No change',
                ];
                $miniChartWidth = 360;
                $miniChartHeight = 170;
                $miniChartLeft = 46;
                $miniChartRight = 16;
                $miniChartTop = 18;
                $miniChartBottom = 34;
                $miniPlotWidth = $miniChartWidth - $miniChartLeft - $miniChartRight;
                $miniPlotHeight = $miniChartHeight - $miniChartTop - $miniChartBottom;
                $miniLabelCount = max(1, count($expenseAnalytics['labels']));
            @endphp

            <div class="grid gap-4 xl:grid-cols-2">
                @foreach($expenseAnalytics['series'] as $expenseSeries)
                    @php
                        $chartId = 'expense-trend-' . $loop->index;
                        $lineGradientId = $chartId . '-line-fill';
                        $barGradientId = $chartId . '-bar-fill';
                        $seriesMax = max(1, (float) collect($expenseSeries['values'])->max('amount'));
                        $seriesPoints = collect($expenseSeries['values'])->map(function ($point, $pointIndex) use ($miniChartLeft, $miniPlotWidth, $miniPlotHeight, $miniChartTop, $seriesMax, $miniLabelCount) {
                            $x = $miniChartLeft + ($miniLabelCount === 1 ? 0 : (($miniPlotWidth / ($miniLabelCount - 1)) * $pointIndex));
                            $y = $miniChartTop + $miniPlotHeight - (((float) $point['amount'] / $seriesMax) * $miniPlotHeight);

                            return number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
                        })->implode(' ');
                        $seriesAreaPoints = $miniChartLeft . ',' . ($miniChartTop + $miniPlotHeight) . ' ' . $seriesPoints . ' ' . ($miniChartLeft + $miniPlotWidth) . ',' . ($miniChartTop + $miniPlotHeight);
                    @endphp

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $expenseSeries['color'] }}"></span>
                                    <h4 class="truncate text-sm font-semibold text-gray-900">{{ $expenseSeries['label'] }}</h4>
                                </div>
                                <p class="mt-2 text-2xl font-semibold tabular-nums text-gray-900">₱{{ number_format($expenseSeries['today'], 2) }}</p>
                            </div>
                            <div class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $directionClasses[$expenseSeries['direction']] }}">
                                {{ $directionLabels[$expenseSeries['direction']] }}
                                @if($expenseSeries['direction'] !== 'flat')
                                    ₱{{ number_format(abs($expenseSeries['change']), 2) }}
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-100 bg-gradient-to-b from-slate-50 to-white px-3 py-3">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Line Trend</p>
                                <p class="text-xs font-medium text-gray-500">Weekly</p>
                            </div>
                        <div class="overflow-x-auto">
                            <svg viewBox="0 0 {{ $miniChartWidth }} {{ $miniChartHeight }}" role="img" aria-label="{{ $expenseSeries['label'] }} daily expense line chart" class="min-w-[340px] w-full">
                                <defs>
                                    <linearGradient id="{{ $lineGradientId }}" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="{{ $expenseSeries['color'] }}" stop-opacity="0.2" />
                                        <stop offset="100%" stop-color="{{ $expenseSeries['color'] }}" stop-opacity="0.02" />
                                    </linearGradient>
                                </defs>
                                @for($i = 0; $i <= 3; $i++)
                                    @php
                                        $gridValue = ($seriesMax / 3) * $i;
                                        $y = $miniChartTop + $miniPlotHeight - (($gridValue / $seriesMax) * $miniPlotHeight);
                                    @endphp
                                    <line x1="{{ $miniChartLeft }}" y1="{{ $y }}" x2="{{ $miniChartWidth - $miniChartRight }}" y2="{{ $y }}" stroke="#dbe3ee" stroke-width="1" />
                                    <text x="{{ $miniChartLeft - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-gray-500" style="font-size: 10px;">₱{{ number_format($gridValue, 0) }}</text>
                                @endfor

                                @foreach($expenseAnalytics['labels'] as $labelIndex => $label)
                                    @php
                                        $x = $miniChartLeft + ($miniLabelCount === 1 ? 0 : (($miniPlotWidth / ($miniLabelCount - 1)) * $labelIndex));
                                    @endphp
                                    <text x="{{ $x }}" y="{{ $miniChartHeight - 12 }}" text-anchor="middle" class="fill-gray-500" style="font-size: 10px;">{{ $label }}</text>
                                @endforeach

                                <polygon points="{{ $seriesAreaPoints }}" fill="url(#{{ $lineGradientId }})" />
                                <polyline points="{{ $seriesPoints }}" fill="none" stroke="{{ $expenseSeries['color'] }}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" filter="drop-shadow(0 5px 8px rgba(15, 23, 42, 0.12))" />

                                @foreach($expenseSeries['values'] as $pointIndex => $point)
                                    @php
                                        $x = $miniChartLeft + ($miniLabelCount === 1 ? 0 : (($miniPlotWidth / ($miniLabelCount - 1)) * $pointIndex));
                                        $y = $miniChartTop + $miniPlotHeight - (((float) $point['amount'] / $seriesMax) * $miniPlotHeight);
                                    @endphp
                                    <circle
                                        cx="{{ $x }}"
                                        cy="{{ $y }}"
                                        r="10"
                                        fill="transparent"
                                        data-expense-chart-point
                                        data-tooltip-title="{{ $expenseSeries['label'] }}"
                                        data-tooltip-date="{{ $point['label'] }}"
                                        data-tooltip-amount="₱{{ number_format((float) $point['amount'], 2) }}"
                                    ></circle>
                                    <circle
                                        cx="{{ $x }}"
                                        cy="{{ $y }}"
                                        r="5"
                                        fill="#ffffff"
                                        stroke="{{ $expenseSeries['color'] }}"
                                        stroke-width="3"
                                        data-expense-chart-point
                                        data-tooltip-title="{{ $expenseSeries['label'] }}"
                                        data-tooltip-date="{{ $point['label'] }}"
                                        data-tooltip-amount="₱{{ number_format((float) $point['amount'], 2) }}"
                                    >
                                        <title>{{ $expenseSeries['label'] }} {{ $point['label'] }}: ₱{{ number_format((float) $point['amount'], 2) }}</title>
                                    </circle>
                                @endforeach
                            </svg>
                        </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-100 bg-gradient-to-b from-slate-50 to-white px-3 py-3">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Daily Bar Chart</p>
                                <p class="text-xs font-medium text-gray-500">Max ₱{{ number_format($seriesMax, 2) }}</p>
                            </div>
                            @php
                                $barChartWidth = 360;
                                $barChartHeight = 116;
                                $barChartLeft = 20;
                                $barChartRight = 14;
                                $barChartTop = 10;
                                $barChartBottom = 24;
                                $barPlotWidth = $barChartWidth - $barChartLeft - $barChartRight;
                                $barPlotHeight = $barChartHeight - $barChartTop - $barChartBottom;
                                $barCount = max(1, count($expenseSeries['values']));
                                $barSlotWidth = $barPlotWidth / $barCount;
                                $barWidth = min(28, max(12, $barSlotWidth * 0.58));
                            @endphp

                            <svg viewBox="0 0 {{ $barChartWidth }} {{ $barChartHeight }}" role="img" aria-label="{{ $expenseSeries['label'] }} daily expense bar chart" class="w-full">
                                <defs>
                                    <linearGradient id="{{ $barGradientId }}" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="{{ $expenseSeries['color'] }}" stop-opacity="0.86" />
                                        <stop offset="100%" stop-color="#0f172a" stop-opacity="0.95" />
                                    </linearGradient>
                                </defs>
                                <line x1="{{ $barChartLeft }}" y1="{{ $barChartTop + $barPlotHeight }}" x2="{{ $barChartWidth - $barChartRight }}" y2="{{ $barChartTop + $barPlotHeight }}" stroke="#cbd5e1" stroke-width="1" />
                                @foreach($expenseSeries['values'] as $point)
                                    @php
                                        $barAmount = (float) $point['amount'];
                                        $barHeight = $barAmount > 0 ? max(5, (($barAmount / $seriesMax) * $barPlotHeight)) : 0;
                                        $x = $barChartLeft + ($barSlotWidth * $loop->index) + (($barSlotWidth - $barWidth) / 2);
                                        $y = $barChartTop + $barPlotHeight - $barHeight;
                                        $labelX = $barChartLeft + ($barSlotWidth * $loop->index) + ($barSlotWidth / 2);
                                    @endphp
                                    @if($barAmount > 0)
                                        <text x="{{ $labelX }}" y="{{ max(9, $y - 7) }}" text-anchor="middle" class="fill-slate-900" style="font-size: 10px; font-weight: 700;">₱{{ number_format($barAmount, 0) }}</text>
                                    @else
                                        <text x="{{ $labelX }}" y="{{ $barChartTop + $barPlotHeight - 7 }}" text-anchor="middle" class="fill-slate-500" style="font-size: 10px; font-weight: 700;">₱0</text>
                                    @endif
                                    <rect
                                        x="{{ $x }}"
                                        y="{{ $y }}"
                                        width="{{ $barWidth }}"
                                        height="{{ $barHeight }}"
                                        rx="7"
                                        fill="url(#{{ $barGradientId }})"
                                        filter="drop-shadow(0 8px 12px rgba(15, 23, 42, 0.16))"
                                        data-expense-chart-point
                                        data-tooltip-title="{{ $expenseSeries['label'] }}"
                                        data-tooltip-date="{{ $point['label'] }}"
                                        data-tooltip-amount="₱{{ number_format($barAmount, 2) }}"
                                    >
                                        <title>{{ $expenseSeries['label'] }} {{ $point['label'] }}: ₱{{ number_format($barAmount, 2) }}</title>
                                    </rect>
                                    <text x="{{ $labelX }}" y="{{ $barChartHeight - 7 }}" text-anchor="middle" class="fill-gray-500" style="font-size: 10px;">{{ $point['label'] }}</text>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

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
                    <span class="font-medium">{{ $peso($statement['collections']) }}</span>
                </div>
                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-600">Collections from Today's Sales:</span>
                    <span class="font-medium">{{ $peso($statement['collections_from_today_sales']) }}</span>
                </div>
                <div class="mb-2 flex justify-between text-sm">
                    <span class="text-gray-600">Collections from Previous Balances:</span>
                    <span class="font-medium">{{ $peso($statement['collections_from_previous_balances']) }}</span>
                </div>
            </div>

            <div class="mb-4 border-t border-gray-200 pt-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Income Statement</h3>
                <div class="space-y-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Gross Sales</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['gross_sales']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Sales Discounts</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['sales_discounts']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Net Sales</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['net_sales']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Cost of Sales</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['cost_of_sales']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Gross Profit</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['gross_profit']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Selling, General and Administrative Expenses</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['selling_general_and_administrative_expenses']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Operating Income</span>
                            <span class="font-semibold {{ $operatingIncomeIsLoss ? 'text-red-700' : 'text-gray-900' }}">{{ $peso($statement['operating_income']) }}</span>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">Less: Loss on Sale</span>
                            <span class="font-semibold text-gray-900">{{ $peso($statement['loss_on_sale']) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 border-t border-gray-200 pt-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Daily Expenses Total:</span>
                        <span class="font-semibold">{{ $peso($dailyExpensesTotal) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 text-sm">
                        <span class="font-semibold text-gray-600">Net Income:</span>
                        <span class="font-bold {{ $netIncomeIsLoss ? 'text-red-600' : 'text-green-600' }}">{{ $peso($statement['net_income']) }}</span>
                    </div>
                </div>
            </div>

            <div class="mb-4 border-t border-gray-200 pt-4">
                <div class="rounded-lg bg-orange-50 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-orange-900">Outstanding Receivable Balance</span>
                        <span class="font-semibold text-orange-900">{{ $peso($statement['outstanding_receivable_balance']) }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-orange-100 pt-2 text-sm">
                        <span class="font-medium text-orange-900">Collections from Today's Sales</span>
                        <span class="font-semibold text-orange-900">{{ $peso($statement['collections_from_today_sales']) }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-orange-900">Collections from Previous Balances</span>
                        <span class="font-semibold text-orange-900">{{ $peso($statement['collections_from_previous_balances']) }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-orange-100 pt-2 text-sm">
                        <span class="font-medium text-orange-900">Cash on Hand</span>
                        <span class="font-semibold text-orange-900">{{ $peso($statement['cash_on_hand']) }}</span>
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
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-daily-expenses-form]');

        if (!form) {
            return;
        }

        const rows = form.querySelector('[data-expense-rows]');
        const template = form.querySelector('[data-other-expense-template]');
        const addButton = form.querySelector('[data-add-other-expense]');
        const mode = form.querySelector('[data-entry-form-mode]');
        const expensesPanel = form.querySelector('[data-expenses-panel]');
        const lossPanel = form.querySelector('[data-loss-panel]');
        const submitLabel = form.querySelector('[data-submit-label]');
        const otherCategory = @json(\App\Models\FinancialStatementEntry::EXPENSE_CATEGORY_OTHER);
        let nextOtherIndex = Number(form.dataset.nextOtherIndex || 0);

        const addOtherRow = (descriptionValue = '', amountValue = '', forcedIndex = null) => {
            if (!rows || !template) {
                return;
            }

            const index = forcedIndex ?? nextOtherIndex++;
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('[data-other-expense-row]');
            const category = fragment.querySelector('[data-other-category]');
            const description = fragment.querySelector('[data-other-description]');
            const amount = fragment.querySelector('[data-other-amount]');

            category.name = `expenses[${index}][category]`;
            description.name = `expenses[${index}][description]`;
            amount.name = `expenses[${index}][amount]`;
            category.value = otherCategory;
            description.value = descriptionValue;
            amount.value = amountValue;

            row.querySelector('[data-remove-other-expense]').addEventListener('click', () => {
                row.remove();
            });

            rows.appendChild(fragment);
        };

        addButton?.addEventListener('click', () => addOtherRow());

        const syncMode = () => {
            const isLoss = mode?.value === 'loss_on_sale';

            expensesPanel?.classList.toggle('hidden', isLoss);
            lossPanel?.classList.toggle('hidden', !isLoss);

            if (submitLabel) {
                submitLabel.textContent = isLoss ? 'Save Loss on Sale' : 'Save Expenses';
            }
        };

        mode?.addEventListener('change', syncMode);
        syncMode();

        form.querySelectorAll('[data-old-other-expense]').forEach((oldExpense) => {
            addOtherRow(
                oldExpense.dataset.description || '',
                oldExpense.dataset.amount || '',
                Number(oldExpense.dataset.index)
            );
        });

        document.querySelectorAll('[data-delete-entry-form]').forEach((deleteForm) => {
            deleteForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const entryName = deleteForm.dataset.deleteEntryName || 'this entry';
                const entryAmount = deleteForm.dataset.deleteEntryAmount || '';
                const submitButton = deleteForm.querySelector('button[type="submit"]');

                if (!window.Swal) {
                    if (window.confirm(`Remove ${entryName}?`)) {
                        deleteForm.submit();
                    }

                    return;
                }

                const result = await window.Swal.fire({
                    title: 'Remove entry?',
                    text: `${entryName}${entryAmount ? ` (${entryAmount})` : ''} will be removed from this statement.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Remove',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    focusCancel: true,
                });

                if (result.isConfirmed) {
                    try {
                        submitButton?.setAttribute('disabled', 'disabled');

                        const response = await fetch(deleteForm.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(deleteForm),
                        });

                        if (!response.ok) {
                            throw new Error('Delete request failed.');
                        }

                        const payload = await response.json();
                        const entryRow = deleteForm.closest('[data-entry-row]');
                        const entryType = payload.entry_type || entryRow?.dataset.entryType;
                        const countLabel = `${payload.entries_count} item${payload.entries_count === 1 ? '' : 's'}`;

                        entryRow?.remove();

                        document.querySelectorAll(`[data-entry-group="${entryType}"] [data-entry-group-count]`).forEach((countEl) => {
                            countEl.textContent = countLabel;
                        });

                        document.querySelectorAll(`[data-entry-group="${entryType}"] [data-entry-group-total]`).forEach((totalEl) => {
                            totalEl.textContent = payload.group_total_formatted;
                        });

                        document.querySelectorAll(`[data-modal-entry-count="${entryType}"]`).forEach((countEl) => {
                            countEl.textContent = countLabel;
                        });

                        document.querySelectorAll(`[data-modal-entry-total="${entryType}"]`).forEach((totalEl) => {
                            totalEl.textContent = payload.group_total_formatted;
                        });

                        if (payload.entries_count === 0) {
                            document.querySelector(`[data-entry-list="${entryType}"]`)?.classList.add('hidden');
                            document.querySelector(`[data-entry-empty="${entryType}"]`)?.classList.remove('hidden');
                        }

                        window.Swal.fire({
                            title: 'Removed',
                            text: payload.message || 'Entry removed successfully.',
                            icon: 'success',
                            timer: 1400,
                            showConfirmButton: false,
                        });
                    } catch (error) {
                        submitButton?.removeAttribute('disabled');

                        window.Swal.fire({
                            title: 'Unable to remove',
                            text: 'Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                        });
                    }
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const tooltipTargets = document.querySelectorAll('[data-expense-chart-point]');

        if (tooltipTargets.length === 0) {
            return;
        }

        const tooltip = document.createElement('div');
        tooltip.className = 'pointer-events-none fixed z-[1000] hidden rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 shadow-lg';
        tooltip.style.maxWidth = '14rem';
        document.body.appendChild(tooltip);

        const moveTooltip = (event) => {
            const offset = 14;
            const tooltipRect = tooltip.getBoundingClientRect();
            let left = event.clientX + offset;
            let top = event.clientY + offset;

            if (left + tooltipRect.width > window.innerWidth - 8) {
                left = event.clientX - tooltipRect.width - offset;
            }

            if (top + tooltipRect.height > window.innerHeight - 8) {
                top = event.clientY - tooltipRect.height - offset;
            }

            tooltip.style.left = `${Math.max(8, left)}px`;
            tooltip.style.top = `${Math.max(8, top)}px`;
        };

        tooltipTargets.forEach((target) => {
            target.addEventListener('mouseenter', (event) => {
                tooltip.innerHTML = `
                    <div class="font-semibold text-slate-900">${target.dataset.tooltipTitle || 'Expense'}</div>
                    <div class="mt-1 text-slate-500">${target.dataset.tooltipDate || ''}</div>
                    <div class="mt-1 font-semibold tabular-nums text-slate-900">${target.dataset.tooltipAmount || '₱0.00'}</div>
                `;
                tooltip.classList.remove('hidden');
                moveTooltip(event);
            });

            target.addEventListener('mousemove', moveTooltip);
            target.addEventListener('mouseleave', () => {
                tooltip.classList.add('hidden');
            });
        });
    });

    function printFinancialStatement() {
        window.printReceipt(
            'financial-statement-print-content',
            'Income Statement - {{ $statementDateCarbon->format('Y-m-d') }}'
        );
    }
</script>
@endsection
